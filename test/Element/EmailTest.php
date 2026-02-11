<?php

declare(strict_types=1);

namespace LaminasTest\Form\Element;

use Laminas\Form\Element\Email as EmailElement;
use Laminas\Validator\Explode;
use Laminas\Validator\Regex;
use Laminas\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function method_exists;

use const PHP_VERSION_ID;

final class EmailTest extends TestCase
{
    public function testProvidesInputSpecificationThatIncludesDefaultValidators(): void
    {
        $element = new EmailElement();

        $inputSpec = $element->getInputSpecification();
        self::assertArrayHasKey('validators', $inputSpec);
        self::assertIsArray($inputSpec['validators']);

        $expectedValidators = [
            Regex::class,
        ];
        foreach ($inputSpec['validators'] as $i => $validator) {
            $class = $validator::class;
            self::assertEquals($expectedValidators[$i], $class);
        }
    }

    /** @return list<array{0: array<string, scalar>, 1: list<class-string<ValidatorInterface>>}> */
    public static function emailAttributesDataProvider(): array
    {
        return [
                  // attributes               // expectedValidators
            [['multiple' => true], [Explode::class]],
            [['multiple' => false], [Regex::class]],
        ];
    }

    /**
     * @param array<string, scalar> $attributes
     * @param list<class-string<ValidatorInterface>> $expectedValidators
     */
    #[DataProvider('emailAttributesDataProvider')]
    public function testProvidesInputSpecificationBasedOnAttributes(array $attributes, array $expectedValidators): void
    {
        $element = new EmailElement();
        $element->setAttributes($attributes);

        $inputSpec = $element->getInputSpecification();
        self::assertArrayHasKey('validators', $inputSpec);
        self::assertIsArray($inputSpec['validators'] ?? null);

        foreach ($inputSpec['validators'] as $i => $validator) {
            $class = $validator::class;
            self::assertEquals($expectedValidators[$i], $class);
            switch ($class) {
                case Explode::class:
                    self::assertInstanceOf(Regex::class, $this->getExplodeValidator($validator));
                    break;
                default:
                    break;
            }
        }
    }

    private function getExplodeValidator(Explode $validator): ValidatorInterface
    {
        if (method_exists($validator, 'getValidator')) {
            return $validator->getValidator();
        }

        $property = new ReflectionProperty($validator, 'validator');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        $innerValidator = $property->getValue($validator);
        self::assertInstanceOf(ValidatorInterface::class, $innerValidator);

        return $innerValidator;
    }
}
