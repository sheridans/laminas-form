<?php

declare(strict_types=1);

namespace LaminasTest\Form\Element;

use ArrayIterator;
use ArrayObject;
use Laminas\Captcha;
use Laminas\Captcha\Dumb;
use Laminas\Form\Element\Captcha as CaptchaElement;
use Laminas\Form\Exception\InvalidArgumentException;
use Laminas\Form\Factory;
use LaminasTest\Form\TestAsset;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function array_shift;
use function class_exists;

final class CaptchaTest extends TestCase
{
    public function testCaptchaIsUndefinedByDefault(): void
    {
        $element = new CaptchaElement();
        self::assertNull($element->getCaptcha());
    }

    public function testCaptchaIsMutable(): void
    {
        $this->skipIfCaptchaMissing();

        $element = new CaptchaElement();

        // by instance
        $captcha = new Captcha\Dumb();
        $element->setCaptcha($captcha);
        self::assertSame($captcha, $element->getCaptcha());

        // by array
        $captcha = [
            'class' => 'dumb',
        ];
        $element->setCaptcha($captcha);
        self::assertInstanceOf(Dumb::class, $element->getCaptcha());

        // by traversable
        $captcha = new ArrayObject([
            'class' => 'dumb',
        ]);
        $element->setCaptcha($captcha);
        self::assertInstanceOf(Dumb::class, $element->getCaptcha());
    }

    public function testCaptchaWithNullRaisesException(): void
    {
        $element = new CaptchaElement();
        $this->expectException(InvalidArgumentException::class);
        $element->setCaptcha(null);
    }

    public function testSettingCaptchaSetsCaptchaAttribute(): void
    {
        $this->skipIfCaptchaMissing();

        $element = new CaptchaElement();
        $captcha = new Captcha\Dumb();
        $element->setCaptcha($captcha);
        self::assertSame($captcha, $element->getCaptcha());
    }

    public function testCreatingCaptchaElementViaFormFactoryWillCreateCaptcha(): void
    {
        $this->skipIfCaptchaMissing();

        $factory = new Factory();
        $element = $factory->createElement([
            'type'    => CaptchaElement::class,
            'name'    => 'foo',
            'options' => [
                'captcha' => [
                    'class' => 'dumb',
                ],
            ],
        ]);
        self::assertInstanceOf(CaptchaElement::class, $element);
        $captcha = $element->getCaptcha();
        self::assertInstanceOf(Dumb::class, $captcha);
    }

    public function testProvidesInputSpecificationThatIncludesCaptchaAsValidator(): void
    {
        $this->skipIfCaptchaMissing();

        $element = new CaptchaElement();
        $captcha = new Captcha\Dumb();
        $element->setCaptcha($captcha);

        $inputSpec = $element->getInputSpecification();
        self::assertArrayHasKey('validators', $inputSpec);
        self::assertIsArray($inputSpec['validators']);
        $test = array_shift($inputSpec['validators']);
        self::assertSame($captcha, $test);
    }

    #[Group('issue-3446')]
    public function testAllowsPassingTraversableOptionsToConstructor(): void
    {
        $this->skipIfCaptchaMissing();

        $options = new TestAsset\IteratorAggregate(new ArrayIterator([
            'captcha' => [
                'class' => 'dumb',
            ],
        ]));
        $element = new CaptchaElement('captcha', $options);
        $captcha = $element->getCaptcha();
        self::assertInstanceOf(Dumb::class, $captcha);
    }

    private function skipIfCaptchaMissing(): void
    {
        if (class_exists(Dumb::class) && class_exists(Captcha\Factory::class)) {
            return;
        }

        self::markTestSkipped('laminas-captcha is not installed in this test matrix');
    }
}
