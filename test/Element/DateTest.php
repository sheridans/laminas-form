<?php

declare(strict_types=1);

namespace LaminasTest\Form\Element;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Laminas\Form\Element\Date as DateElement;
use Laminas\Form\Exception\InvalidArgumentException;
use Laminas\Validator\Date;
use Laminas\Validator\DateStep;
use Laminas\Validator\GreaterThan;
use Laminas\Validator\LessThan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function class_exists;
use function date;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function method_exists;

use const PHP_VERSION_ID;

#[CoversClass(DateElement::class)]
final class DateTest extends TestCase
{
    /**
     * Stores the original set timezone
     *
     * @var non-empty-string
     */
    private string $originaltimezone;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->originaltimezone = date_default_timezone_get();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        date_default_timezone_set($this->originaltimezone);
    }

    public function testProvidesDefaultInputSpecification(): void
    {
        $element = new DateElement('foo');

        $inputSpec = $element->getInputSpecification();
        self::assertArrayHasKey('validators', $inputSpec);
        self::assertIsArray($inputSpec['validators']);

        $expectedClasses = [
            Date::class,
            DateStep::class,
        ];
        foreach ($inputSpec['validators'] as $validator) {
            $class = $validator::class;
            self::assertContains($class, $expectedClasses, $class);
            switch ($class) {
                case DateStep::class:
                    $dateInterval = new DateInterval('P1D');
                    self::assertEquals($dateInterval, $this->getDateStepStep($validator));
                    self::assertEquals(date('Y-m-d', 0), $this->getDateStepBaseValue($validator));
                    break;
                default:
                    break;
            }
        }
    }

    public function testProvidesInputSpecificationThatIncludesValidatorsBasedOnAttributes(): void
    {
        $this->skipIfComparisonValidatorsMissing();

        $element = new DateElement('foo');
        $element->setAttributes([
            'inclusive' => true,
            'min'       => '2000-01-01',
            'max'       => '2001-01-01',
            'step'      => '1',
        ]);

        $inputSpec = $element->getInputSpecification();
        self::assertArrayHasKey('validators', $inputSpec);
        self::assertIsArray($inputSpec['validators']);

        $expectedClasses = [
            Date::class,
            GreaterThan::class,
            LessThan::class,
            DateStep::class,
        ];
        foreach ($inputSpec['validators'] as $validator) {
            $class = $validator::class;
            self::assertContains($class, $expectedClasses, $class);
            switch ($class) {
                case GreaterThan::class:
                    self::assertTrue($validator->getInclusive());
                    self::assertEquals('2000-01-01', $validator->getMin());
                    break;
                case LessThan::class:
                    self::assertTrue($validator->getInclusive());
                    self::assertEquals('2001-01-01', $validator->getMax());
                    break;
                case DateStep::class:
                    $dateInterval = new DateInterval('P1D');
                    self::assertEquals($dateInterval, $this->getDateStepStep($validator));
                    self::assertEquals('2000-01-01', $this->getDateStepBaseValue($validator));
                    break;
                default:
                    break;
            }
        }
    }

    public function testValueReturnedFromComposedDateTimeIsRfc3339FullDateFormat(): void
    {
        $element = new DateElement('foo');
        $date    = new DateTime();
        $element->setValue($date);
        $value = $element->getValue();
        self::assertEquals($date->format('Y-m-d'), $value);
    }

    public function testCorrectFormatPassedToDateValidator(): void
    {
        $element = new DateElement('foo');
        $element->setAttributes([
            'min' => '01-01-2012',
            'max' => '31-12-2012',
        ]);
        $element->setFormat('d-m-Y');

        $this->skipIfComparisonValidatorsMissing();

        $inputSpec = $element->getInputSpecification();
        foreach ($inputSpec['validators'] as $validator) {
            switch ($validator::class) {
                case DateStep::class:
                case Date::class:
                    self::assertEquals('d-m-Y', $validator->getFormat());
                    break;
            }
        }
    }

    #[Group('issue-6245')]
    public function testStepValidatorIgnoresDaylightSavings(): void
    {
        date_default_timezone_set('Europe/London');

        $element = new DateElement('foo');

        $inputSpec = $element->getInputSpecification();
        foreach ($inputSpec['validators'] as $validator) {
            switch ($validator::class) {
                case DateStep::class:
                    self::assertTrue($validator->isValid('2013-12-25'));
                    break;
            }
        }
    }

    public function testFailsWithInvalidMinSpecification(): void
    {
        $element = new DateElement('foo');
        $element->setAttributes([
            'inclusive' => true,
            'min'       => '2000-01-01T00',
            'step'      => '1',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $element->getInputSpecification();
    }

    public function testFailsWithInvalidMaxSpecification(): void
    {
        $element = new DateElement('foo');
        $element->setAttributes([
            'inclusive' => true,
            'max'       => '2001-01-01T00',
            'step'      => '1',
        ]);
        $this->expectException(InvalidArgumentException::class);
        $element->getInputSpecification();
    }

    private function getDateStepStep(DateStep $validator): DateInterval
    {
        if (method_exists($validator, 'getStep')) {
            return $validator->getStep();
        }

        $property = new ReflectionProperty($validator, 'step');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        return $property->getValue($validator);
    }

    private function getDateStepBaseValue(DateStep $validator): string
    {
        if (method_exists($validator, 'getBaseValue')) {
            return (string) $validator->getBaseValue();
        }

        $property = new ReflectionProperty($validator, 'baseValue');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $baseValue = $property->getValue($validator);

        if ($baseValue instanceof DateTimeInterface) {
            return $baseValue->format('Y-m-d');
        }

        /** @psalm-suppress MixedReturnStatement */
        return (string) $baseValue;
    }

    private function skipIfComparisonValidatorsMissing(): void
    {
        if (! class_exists(GreaterThan::class) || ! class_exists(LessThan::class)) {
            self::markTestSkipped('laminas-validator comparison classes are not available in this test matrix');
        }
    }
}
