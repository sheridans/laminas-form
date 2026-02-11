<?php

declare(strict_types=1);

namespace LaminasTest\Form\Annotation;

use Laminas\Form\Annotation;

use const PHP_MAJOR_VERSION;

final class AttributeBuilderTest extends AbstractBuilderTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_MAJOR_VERSION < 8) {
            $this->markTestSkipped('Attribute builder should only throw an exception prior to PHP 8.');
        }
    }

    protected function createBuilder(): Annotation\AbstractBuilder
    {
        $builder = new Annotation\AttributeBuilder();
        $this->configureInputFilterInputFactoryForV3($builder);
        return $builder;
    }
}
