<?php

declare(strict_types=1);

namespace LaminasTest\Form\Element;

use Laminas\Filter\ConfigProvider as FilterConfigProvider;
use Laminas\Form\Element\File as FileElement;
use Laminas\Form\Form;
use Laminas\InputFilter\ConfigProvider as InputFilterConfigProvider;
use Laminas\InputFilter\Factory as InputFilterFactory;
use Laminas\InputFilter\FileInput;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ConfigProvider as ValidatorConfigProvider;
use PHPUnit\Framework\TestCase;

use function array_replace_recursive;
use function method_exists;

final class FileTest extends TestCase
{
    public function testProvidesDefaultInputSpecification(): void
    {
        $element = new FileElement('foo');
        self::assertEquals('file', $element->getAttribute('type'));

        $inputSpec = $element->getInputSpecification();
        $factory   = $this->createInputFilterFactory();
        $input     = $factory->createInput($inputSpec);
        self::assertInstanceOf(FileInput::class, $input);
    }

    public function testWillAddFileEnctypeAttributeToForm(): void
    {
        $file     = new FileElement('foo');
        $formMock = $this->createMock(Form::class);
        $formMock->expects($this->once())
            ->method('setAttribute')
            ->with(
                $this->stringContains('enctype'),
                $this->stringContains('multipart/form-data')
            );
        $file->prepareElement($formMock);
    }

    private function createInputFilterFactory(): InputFilterFactory
    {
        if (! method_exists(InputFilterFactory::class, 'new')) {
            return new InputFilterFactory();
        }

        $container    = new ServiceManager();
        $dependencies = array_replace_recursive(
            (new FilterConfigProvider())->__invoke()['dependencies'] ?? [],
            (new ValidatorConfigProvider())->__invoke()['dependencies'] ?? [],
            (new InputFilterConfigProvider())->__invoke()['dependencies'] ?? [],
        );
        $container->configure($dependencies);

        return InputFilterFactory::new($container);
    }
}
