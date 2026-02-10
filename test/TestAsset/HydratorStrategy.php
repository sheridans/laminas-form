<?php

declare(strict_types=1);

namespace LaminasTest\Form\TestAsset;

use Laminas\Hydrator\Strategy\StrategyInterface;

use function is_array;

final class HydratorStrategy implements StrategyInterface
{
    /**
     * A simulated storage device which is just an array with Car objects.
     */
    private array $simulatedStorageDevice;

    public function __construct()
    {
        $this->simulatedStorageDevice   = [];
        $this->simulatedStorageDevice[] = new HydratorStrategyEntityB(111, 'AAA');
        $this->simulatedStorageDevice[] = new HydratorStrategyEntityB(222, 'BBB');
        $this->simulatedStorageDevice[] = new HydratorStrategyEntityB(333, 'CCC');
    }

    /**
     * @inheritDoc
     */
    public function extract(mixed $value, ?object $object = null): mixed
    {
        $result = [];
        foreach ($value as $instance) {
            $result[] = $instance->getField1();
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function hydrate(mixed $value, ?array $data = null): mixed
    {
        $result = $value;
        if (is_array($value)) {
            $result = [];
            foreach ($value as $field1) {
                $result[] = $this->findEntity($field1);
            }
        }
        return $result;
    }

    /**
     * @return mixed|null
     */
    private function findEntity(mixed $field1)
    {
        $result = null;
        foreach ($this->simulatedStorageDevice as $entity) {
            if ($entity->getField1() === $field1) {
                $result = $entity;
                break;
            }
        }
        return $result;
    }
}
