<?php

declare(strict_types=1);

namespace LaminasTest\Form;

use Laminas\Filter\FilterPluginManager;
use Laminas\Form\Element\Email;
use Laminas\Form\Form;
use Laminas\Form\FormAbstractServiceFactory;
use Laminas\Form\FormElementManager;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\InputFilter\Factory;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterPluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ValidatorPluginManager;
use PHPUnit\Framework\TestCase;

use function method_exists;

final class FormAbstractServiceFactoryTest extends TestCase
{
    private ServiceManager $services;
    private FormAbstractServiceFactory $forms;

    protected function setUp(): void
    {
        $services     = $this->services = new ServiceManager();
        $elements     = new FormElementManager($services);
        $filters      = new FilterPluginManager($services);
        $hydrators    = new HydratorPluginManager($services);
        $inputFilters = new InputFilterPluginManager($services);
        $validators   = new ValidatorPluginManager($services);

        $services->setService(FilterPluginManager::class, $filters);
        $services->setService(FormElementManager::class, $elements);
        $services->setService(HydratorPluginManager::class, $hydrators);
        $services->setService(InputFilterPluginManager::class, $inputFilters);
        $services->setService(ValidatorPluginManager::class, $validators);
        if (method_exists(Factory::class, 'new')) {
            $services->setAllowOverride(true);
            $services->setService(Factory::class, Factory::new($services));
            $services->setAllowOverride(false);
        }

        $forms = $this->forms = new FormAbstractServiceFactory();
        $services->addAbstractFactory($forms);
    }

    public function testMissingConfigServiceIndicatesCannotCreateForm(): void
    {
        self::assertFalse($this->forms->canCreate($this->services, 'foo'));
    }

    public function testMissingFormServicePrefixIndicatesCannotCreateForm(): void
    {
        $this->services->setService('config', []);
        self::assertFalse($this->forms->canCreate($this->services, 'foo'));
    }

    public function testMissingFormManagerConfigIndicatesCannotCreateForm(): void
    {
        $this->services->setService('config', []);
        self::assertFalse($this->forms->canCreate($this->services, 'Form\Foo'));
    }

    public function testInvalidFormManagerConfigIndicatesCannotCreateForm(): void
    {
        $this->services->setService('config', ['forms' => 'string']);
        self::assertFalse($this->forms->canCreate($this->services, 'Form\Foo'));
    }

    public function testEmptyFormManagerConfigIndicatesCannotCreateForm(): void
    {
        $this->services->setService('config', ['forms' => []]);
        self::assertFalse($this->forms->canCreate($this->services, 'Form\Foo'));
    }

    public function testMissingFormConfigIndicatesCannotCreateForm(): void
    {
        $this->services->setService('config', [
            'forms' => [
                'Bar' => [],
            ],
        ]);
        self::assertFalse($this->forms->canCreate($this->services, 'Form\Foo'));
    }

    public function testInvalidFormConfigIndicatesCannotCreateForm(): void
    {
        $this->services->setService('config', [
            'forms' => [
                'Foo' => 'string',
            ],
        ]);
        self::assertFalse($this->forms->canCreate($this->services, 'Foo'));
    }

    public function testEmptyFormConfigIndicatesCannotCreateForm(): void
    {
        $this->services->setService('config', [
            'forms' => [
                'Foo' => [],
            ],
        ]);
        self::assertFalse($this->forms->canCreate($this->services, 'Foo'));
    }

    public function testPopulatedFormConfigIndicatesFormCanBeCreated(): void
    {
        $this->services->setService('config', [
            'forms' => [
                'Foo' => [
                    'type'     => Form::class,
                    'elements' => [],
                ],
            ],
        ]);
        self::assertTrue($this->forms->canCreate($this->services, 'Foo'));
    }

    public function testFormCanBeCreatedViaInteractionOfAllManagers(): void
    {
        $formConfig = [
            'hydrator'     => ObjectPropertyHydrator::class,
            'type'         => Form::class,
            'elements'     => [
                [
                    'spec' => [
                        'type'    => Email::class,
                        'name'    => 'email',
                        'options' => [
                            'label' => 'Your email address',
                        ],
                    ],
                ],
            ],
            'input_filter' => InputFilter::class,
        ];
        $config     = ['forms' => ['Foo' => $formConfig]];
        $this->services->setService('config', $config);
        $form = $this->forms->__invoke($this->services, 'Foo');
        self::assertInstanceOf(Form::class, $form);

        $hydrator = $form->getHydrator();
        self::assertInstanceOf(ObjectPropertyHydrator::class, $hydrator);

        $inputFilter = $form->getInputFilter();
        self::assertInstanceOf(InputFilter::class, $inputFilter);

        if (method_exists($inputFilter, 'getFactory')) {
            $inputFactory = $inputFilter->getFactory();
            self::assertInstanceOf(Factory::class, $inputFactory);
            $filters    = $this->services->get(FilterPluginManager::class);
            $validators = $this->services->get(ValidatorPluginManager::class);
            self::assertSame($filters, $inputFactory->getDefaultFilterChain()->getPluginManager());
            self::assertSame($validators, $inputFactory->getDefaultValidatorChain()->getPluginManager());
        } elseif (method_exists(Factory::class, 'new')) {
            self::assertInstanceOf(Factory::class, $this->services->get(Factory::class));
        }
    }

    public function testFormCanBeCreatedViaInteractionOfAllManagersExceptInputFilterManager(): void
    {
        $formConfig = [
            'hydrator'     => ObjectPropertyHydrator::class,
            'type'         => Form::class,
            'elements'     => [
                [
                    'spec' => [
                        'type'    => Email::class,
                        'name'    => 'email',
                        'options' => [
                            'label' => 'Your email address',
                        ],
                    ],
                ],
            ],
            'input_filter' => [
                'email' => [
                    'required'   => true,
                    'filters'    => [
                        [
                            'name' => 'StringTrim',
                        ],
                    ],
                    'validators' => [
                        [
                            'name' => 'EmailAddress',
                        ],
                    ],
                ],
            ],
        ];
        $config     = ['forms' => ['Foo' => $formConfig]];
        $this->services->setService('config', $config);
        $form = $this->forms->__invoke($this->services, 'Foo');
        self::assertInstanceOf(Form::class, $form);

        $hydrator = $form->getHydrator();
        self::assertInstanceOf(ObjectPropertyHydrator::class, $hydrator);

        $inputFilter = $form->getInputFilter();
        self::assertInstanceOf(InputFilter::class, $inputFilter);

        if (method_exists($inputFilter, 'getFactory')) {
            $inputFactory = $inputFilter->getFactory();
            $filters      = $this->services->get(FilterPluginManager::class);
            $validators   = $this->services->get(ValidatorPluginManager::class);
            self::assertSame($filters, $inputFactory->getDefaultFilterChain()->getPluginManager());
            self::assertSame($validators, $inputFactory->getDefaultValidatorChain()->getPluginManager());
        } elseif (method_exists(Factory::class, 'new')) {
            self::assertInstanceOf(Factory::class, $this->services->get(Factory::class));
        }
    }
}
