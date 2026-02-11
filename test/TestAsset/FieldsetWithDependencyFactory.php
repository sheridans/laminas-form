<?php

declare(strict_types=1);

namespace LaminasTest\Form\TestAsset;

use Laminas\InputFilter\Factory as InputFilterFactory;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

use function method_exists;

final class FieldsetWithDependencyFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, string $name, ?array $options = null): mixed
    {
        $options = $options ?: [];

        $name = null;
        if (isset($options['name'])) {
            $name = $options['name'];
            unset($options['name']);
        }

        $form        = new FieldsetWithDependency($name, $options);
        $inputFilter = method_exists(InputFilterFactory::class, 'new')
            ? new InputFilter(InputFilterFactory::new())
            : new InputFilter();
        $form->setDependency($inputFilter);

        return $form;
    }
}
