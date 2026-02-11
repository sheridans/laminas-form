<?php

declare(strict_types=1);

namespace LaminasTest\Form\Element;

use Laminas\Form\Element\Range as RangeElement;
use Laminas\I18n\Validator\IsFloat;
use Laminas\Validator\GreaterThan;
use Laminas\Validator\LessThan;
use Laminas\Validator\Step;
use PHPUnit\Framework\TestCase;

use function class_exists;
use function extension_loaded;

final class RangeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (! extension_loaded('intl') || ! class_exists(IsFloat::class)) {
            self::markTestSkipped('laminas-i18n IsFloat validator not available in this test matrix');
        }

        parent::setUpBeforeClass();
    }

    public function testProvidesInputSpecificationWithDefaultAttributes(): void
    {
        $element = new RangeElement();

        $inputSpec = $element->getInputSpecification();
        self::assertArrayHasKey('validators', $inputSpec);
        self::assertIsArray($inputSpec['validators']);

        $expectedClasses = [
            IsFloat::class,
            GreaterThan::class,
            LessThan::class,
            Step::class,
        ];
        foreach ($inputSpec['validators'] as $validator) {
            $class = $validator::class;
            self::assertContains($class, $expectedClasses, $class);
            switch ($class) {
                case GreaterThan::class:
                    self::assertTrue($validator->getInclusive());
                    self::assertEquals(0, $validator->getMin());
                    break;
                case LessThan::class:
                    self::assertTrue($validator->getInclusive());
                    self::assertEquals(100, $validator->getMax());
                    break;
                case Step::class:
                    self::assertEquals(1, $validator->getStep());
                    break;
                default:
                    break;
            }
        }
    }

    public function testProvidesInputSpecificationThatIncludesValidator(): void
    {
        $element = new RangeElement();
        $element->setAttributes([
            'inclusive' => true,
            'min'       => 2,
            'max'       => 102,
            'step'      => 2,
        ]);

        $inputSpec = $element->getInputSpecification();
        self::assertArrayHasKey('validators', $inputSpec);
        self::assertIsArray($inputSpec['validators']);

        $expectedClasses = [
            IsFloat::class,
            GreaterThan::class,
            LessThan::class,
            Step::class,
        ];
        foreach ($inputSpec['validators'] as $validator) {
            $class = $validator::class;
            self::assertContains($class, $expectedClasses, $class);
            switch ($class) {
                case GreaterThan::class:
                    self::assertTrue($validator->getInclusive());
                    self::assertEquals(2, $validator->getMin());
                    break;
                case LessThan::class:
                    self::assertTrue($validator->getInclusive());
                    self::assertEquals(102, $validator->getMax());
                    break;
                case Step::class:
                    self::assertEquals(2, $validator->getStep());
                    break;
                default:
                    break;
            }
        }
    }
}
