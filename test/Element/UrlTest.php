<?php

declare(strict_types=1);

namespace LaminasTest\Form\Element;

use Laminas\Form\Element\Url as UrlElement;
use Laminas\Validator\Uri;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function method_exists;
use function ucfirst;

use const PHP_VERSION_ID;

final class UrlTest extends TestCase
{
    public function testProvidesInputSpecificationThatIncludesValidatorsBasedOnAttributes(): void
    {
        $element = new UrlElement();
        $element->setAttributes([
            'allowAbsolute' => true,
            'allowRelative' => false,
        ]);

        $inputSpec = $element->getInputSpecification();
        self::assertArrayHasKey('validators', $inputSpec);
        self::assertIsArray($inputSpec['validators']);

        $expectedClasses = [
            Uri::class,
        ];
        foreach ($inputSpec['validators'] as $validator) {
            $class = $validator::class;
            self::assertContains($class, $expectedClasses, $class);
            switch ($class) {
                case Uri::class:
                    self::assertEquals(true, $this->getUriBoolean($validator, 'allowAbsolute'));
                    self::assertEquals(false, $this->getUriBoolean($validator, 'allowRelative'));
                    break;
                default:
                    break;
            }
        }
    }

    private function getUriBoolean(Uri $validator, string $property): bool
    {
        $method = 'get' . ucfirst($property);
        if (method_exists($validator, $method)) {
            return (bool) $validator->{$method}();
        }

        $reflection = new ReflectionProperty($validator, $property);
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }

        return (bool) $reflection->getValue($validator);
    }
}
