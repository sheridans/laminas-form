<?php

declare(strict_types=1);

namespace LaminasTest\Form\Element;

use Laminas\Filter\StringTrim;
use Laminas\Filter\StripNewlines;
use Laminas\Form\Element\Tel;
use Laminas\Validator\Regex;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function array_diff;
use function array_map;
use function method_exists;
use function str_replace;

use const PHP_VERSION_ID;

final class TelTest extends TestCase
{
    public function testType(): void
    {
        $element = new Tel('test');

        self::assertSame('tel', $element->getAttribute('type'));
    }

    public function testInputSpecification(): void
    {
        $name    = 'test';
        $element = new Tel($name);

        $inputSpec = $element->getInputSpecification();

        self::assertSame($name, $inputSpec['name']);
        self::assertTrue($inputSpec['required']);
        $expectedFilters = [StringTrim::class, StripNewlines::class];
        self::assertInputSpecContainsFilters($expectedFilters, $inputSpec);
        self::assertInputSpecContainsRegexValidator($inputSpec);
    }

    /**
     * @param string[] $expectedFilters
     */
    private function assertInputSpecContainsFilters(array $expectedFilters, array $inputSpec): void
    {
        $actualFilters  = array_map(static fn(array $filterSpec): string => $filterSpec['name'], $inputSpec['filters']);
        $missingFilters = array_diff($expectedFilters, $actualFilters);
        self::assertCount(0, $missingFilters);
    }

    private function assertInputSpecContainsRegexValidator(array $inputSpec): void
    {
        $expectedPattern = '/^[^\r\n]*$/';
        foreach ($inputSpec['validators'] as $validator) {
            if ($validator instanceof Regex) {
                $actual = str_replace(["\r\n", "\r", "\n"], '\r\n', $this->getRegexPattern($validator));
                self::assertSame($expectedPattern, $actual);
                return;
            }
        }

        self::fail('Regex validator not found in input specification');
    }

    private function getRegexPattern(Regex $validator): string
    {
        if (method_exists($validator, 'getPattern')) {
            return $validator->getPattern();
        }

        $property = new ReflectionProperty($validator, 'pattern');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        return (string) $property->getValue($validator);
    }
}
