<?php

declare(strict_types=1);

namespace Laminas\Form\InputFilter;

use Laminas\InputFilter\ArrayInput;

use function is_array;
use function is_scalar;

final class NormalizedArrayInput extends ArrayInput
{
    private ?string $unselectedValue = null;

    public function setUnselectedValue(?string $value): void
    {
        $this->unselectedValue = $value;
    }

    public function setValue(mixed $value): static
    {
        if (is_array($value)) {
            if ($value === []) {
                return parent::setValue([]);
            }

            if ($this->unselectedValue !== null) {
                $allSentinel = true;
                foreach ($value as $entry) {
                    if ((string) $entry !== $this->unselectedValue) {
                        $allSentinel = false;
                        break;
                    }
                }
                if ($allSentinel) {
                    return parent::setValue([]);
                }
            }

            return parent::setValue($value);
        }

        if ($this->unselectedValue !== null && is_scalar($value) && (string) $value === $this->unselectedValue) {
            return parent::setValue([]);
        }

        return parent::setValue($value);
    }

    public function isValid(array|null $context = null): bool
    {
        // ArrayInput::isValid() unconditionally rejects empty arrays for required inputs.
        // When allowEmpty is set (hidden element use-case), an empty normalised array is valid.
        if ($this->allowEmpty()) {
            $values = $this->getValue();
            if (is_array($values) && $values === []) {
                return true;
            }
        }

        return parent::isValid($context);
    }
}
