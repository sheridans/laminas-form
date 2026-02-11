<?php

declare(strict_types=1);

namespace Laminas\Form\InputFilter\Factory;

use Laminas\Filter\FilterChain;
use Laminas\Filter\FilterPluginManager;
use Laminas\Form\InputFilter\NormalizedArrayInput;
use Laminas\Validator\ValidatorChain;
use Laminas\Validator\ValidatorPluginManager;
use Psr\Container\ContainerInterface;

use function is_string;

/**
 * @internal
 */
final class NormalizedArrayInputFactory
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): NormalizedArrayInput {
        $options ??= [];
        $name      = $options['name'] ?? $requestedName;

        $input = new NormalizedArrayInput(
            new FilterChain($container->get(FilterPluginManager::class)),
            new ValidatorChain($container->get(ValidatorPluginManager::class)),
            $name,
            $options,
        );

        $unselectedValue = $options['options']['unselected_value']
            ?? $options['unselected_value']
            ?? null;
        $input->setUnselectedValue(is_string($unselectedValue) ? $unselectedValue : null);

        return $input;
    }
}
