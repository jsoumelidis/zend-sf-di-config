<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Laminas\ContainerConfigTest\AbstractMezzioContainerConfigTest;
use Laminas\ContainerConfigTest\TestAsset\Delegator;
use Laminas\ContainerConfigTest\TestAsset\DelegatorFactory;
use Laminas\ContainerConfigTest\TestAsset\Factory;
use Laminas\ContainerConfigTest\TestAsset\Service;

class ContainerTest extends AbstractMezzioContainerConfigTest
{
    /**
     * @param array $config
     * @param bool  $servicesAsSynthetic
     *
     * @return ContainerBuilder
     */
    protected function createContainer(array $config, bool $servicesAsSynthetic = false) : ContainerInterface
    {
        $factory = new ContainerFactory();
        $container = $factory(new Config(['dependencies' => $config], $servicesAsSynthetic));

        //Everything should work with compiled container also
        $container->compile();

        return $container;
    }

    public function testServicesAsSyntheticRegister(): void
    {
        $dependencies = [
            'services' => [
                Service::class => $service = new Service(),
            ],
        ];

        $container = $this->createContainer($dependencies, true);

        self::assertTrue($container->has(Service::class));
        self::assertTrue($container->getDefinition(Service::class)->isSynthetic());
    }

    /**
     * @expectedException \Exception
     */
    public function testServicesAsSyntheticCannotBeFetched(): void
    {
        $dependencies = [
            'services' => [
                Service::class => $service = new Service(),
            ],
        ];

        $container = $this->createContainer($dependencies, true);

        $container->get(Service::class);
    }

    public function testStaticMethodCallAsDelegatorFactory(): void
    {
        $dependencies = [
            'invokables' => [
                Service::class,
            ],
            'delegators' => [
                Service::class => [
                    [Assets\DelegatorFactory::class, 'create']
                ],
            ],
        ];

        $container = $this->createContainer($dependencies);

        self::assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);
        self::assertInstanceOf(Service::class, $object);
        self::assertInstanceOf(ContainerInterface::class, $object->injected[0]);
    }

    public function testObjectMethodCallableAsFactory(): void
    {
        $dependencies = [
            'factories' => [
                Service::class => [new Factory(), '__invoke'],
            ],
        ];

        $container = $this->createContainer($dependencies);

        self::assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);

        self::assertInstanceOf(Service::class, $object);
    }

    public function testObjectMethodCallableAsFactoryUsingSyntheticServices(): void
    {
        $dependencies = [
            'factories' => [
                Service::class => [new Factory(), '__invoke'],
            ],
        ];

        $container = $this->createContainer($dependencies, true);

        self::assertTrue($container->has(Service::class));
        $this->expectExceptionMessage(
            'You have requested a synthetic service '.
            '("smsfbridge.Zend\ContainerConfigTest\TestAsset\Service.factory.service"). '.
            'The DIC does not know how to construct this service.'
        );

        $container->get(Service::class);
    }

    public function testObjectAsFactory(): void
    {
        $dependencies = [
            'factories' => [
                Service::class => new Factory(),
            ],
        ];

        $container = $this->createContainer($dependencies);

        self::assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);
        self::assertInstanceOf(Service::class, $object);
    }

    public function testObjectAsFactoryUsingSyntheticServices(): void
    {
        $dependencies = [
            'factories' => [
                Service::class => new Factory(),
            ],
        ];

        $container = $this->createContainer($dependencies, true);

        self::assertTrue($container->has(Service::class));
        $this->expectExceptionMessage(
            'You have requested a synthetic service '.
            '("smsfbridge.Zend\ContainerConfigTest\TestAsset\Service.factory.service"). '.
            'The DIC does not know how to construct this service.'
        );

        $container->get(Service::class);
    }

    public function testObjectMethodCallableAsDelegatorFactory(): void
    {
        $dependencies = [
            'invokables' => [
                Service::class,
            ],
            'delegators' => [
                Service::class => [
                    [new DelegatorFactory(), '__invoke'],
                ],
            ],
        ];

        $container = $this->createContainer($dependencies);

        self::assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);

        self::assertInstanceOf(Delegator::class, $object);
        self::assertInstanceOf(\Closure::class, $callback = $object->callback);
        self::assertInstanceOf(Service::class, $callback());
    }

    public function testObjectMethodCallableAsDelegatorFactoryUsingSyntheticServices(): void
    {
        $dependencies = [
            'invokables' => [
                Service::class,
            ],
            'delegators' => [
                Service::class => [
                    [new DelegatorFactory(), '__invoke'],
                ],
            ],
        ];

        $container = $this->createContainer($dependencies, true);

        self::assertTrue($container->has(Service::class));
        $this->expectExceptionMessage(
            'You have requested a synthetic service '.
            '("smsfbridge.Zend\ContainerConfigTest\TestAsset\Service.delegator.0.service"). '.
            'The DIC does not know how to construct this service.'
        );

        $container->get(Service::class);
    }

    public function testObjectAsDelegatorFactory(): void
    {
        $dependencies = [
            'invokables' => [
                Service::class,
            ],
            'delegators' => [
                Service::class => [
                    new DelegatorFactory(),
                ],
            ],
        ];

        $container = $this->createContainer($dependencies);

        self::assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);

        self::assertInstanceOf(Delegator::class, $object);
        self::assertInstanceOf(\Closure::class, $callback = $object->callback);
        self::assertInstanceOf(Service::class, $callback());
    }

    public function testObjectAsDelegatorFactoryUsingSyntheticServices(): void
    {
        $dependencies = [
            'invokables' => [
                Service::class,
            ],
            'delegators' => [
                Service::class => [
                    new DelegatorFactory(),
                ],
            ],
        ];

        $container = $this->createContainer($dependencies, true);

        self::assertTrue($container->has(Service::class));
        $this->expectExceptionMessage(
            'You have requested a synthetic service '.
            '("smsfbridge.Zend\ContainerConfigTest\TestAsset\Service.delegator.0.service"). '.
            'The DIC does not know how to construct this service.'
        );

        $container->get(Service::class);
    }

    public function testDoesNotAcceptDelegatorOnUndefinedServices(): void
    {
        $dependencies = [
            'delegators' => [
                'myservice' => [
                    DelegatorFactory::class,
                ],
            ],
        ];

        $this->expectException(\UnexpectedValueException::class);

        $this->createContainer($dependencies);
    }

    /**
     * @throws \Exception
     */
    public function testCreatesPrivateDelegatorForPublicAliasedPrivateServices(): void
    {
        $builder = new ContainerBuilder();

        //Given a private service with id Service::class
        $serviceDefinition = $builder
            ->register(Service::class, Service::class)
            ->setPublic(false);

        //Given a public alias 'myalias' to Service::class service id
        $alias = new Alias(Service::class);
        $alias->setPublic(true);

        $builder->setAlias('myalias', $alias);

        $dependencies = [
            'delegators' => [
                Service::class => [
                    DelegatorFactory::class,
                ],
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $config->configureContainerBuilder($builder);

        self::assertTrue($builder->hasDefinition(Service::class));

        $definition = $builder->getDefinition(Service::class);

        self::assertEquals($serviceDefinition->isPublic(), $definition->isPublic());

        $builder->compile();

        self::assertFalse($builder->has(Service::class));
        self::assertTrue($builder->has('myalias'));

        /** @var Delegator $service */
        $service = $builder->get('myalias');

        self::assertInstanceOf(Delegator::class, $service);
        self::assertInstanceOf(\Closure::class, $service->callback);
    }
}
