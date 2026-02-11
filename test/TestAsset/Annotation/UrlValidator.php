<?php

declare(strict_types=1);

namespace LaminasTest\Form\TestAsset\Annotation;

use Laminas\Validator\ValidatorInterface;

final class UrlValidator implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function isValid(mixed $value): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMessages()
    {
        return [];
    }
}
