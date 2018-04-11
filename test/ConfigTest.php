<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnexpectedValueException;
use Zend\ContainerConfigTest\TestAsset\Delegator;
use Zend\ContainerConfigTest\TestAsset\DelegatorFactory;
use Zend\ContainerConfigTest\TestAsset\Service;

class ConfigTest extends TestCase
{
    /**
     * @var ContainerFactory
     */
    protected $containerFactory;

    public function setUp()
    {
        parent::setUp();

        $this->containerFactory = new ContainerFactory();
    }

    public function testServicesAsSyntheticRegister(): void
    {
        $dependencies = [
            'services' => [
                Service::class => $service = new Service(),
            ],
        ];

        $config = new Config(['dependencies' => $dependencies], true);
        $container = $this->containerFactory->__invoke($config);

        $this->assertTrue($container->has(Service::class));
        $this->assertTrue($container->getDefinition(Service::class)->isSynthetic());
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

        $config = new Config(['dependencies' => $dependencies], true);

        $this->containerFactory->__invoke($config)->get(Service::class);
    }

    public function testStaticMethodCallAsDelegatorFactory(): void
    {
        $this->markTestSkipped();
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

        $container = $this->containerFactory->__invoke(new Config(['dependencies' => $dependencies]));

        $this->assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);
        $this->assertInstanceOf(Service::class, $object);
        $this->assertInstanceOf(ContainerInterface::class, $object->injected[0]);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUnexpectedValueAsDelegatorFactory(): void
    {
        $this->markTestSkipped();
        $dependencies = [
            'invokables' => [
                Service::class,
            ],
            'delegators' => [
                Service::class => [
                    5,
                ],
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $this->containerFactory->__invoke($config);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUnexpectedClosureAsDelegatorFactory(): void
    {
        $this->markTestSkipped();
        $dependencies = [
            'invokables' => [
                Service::class => Service::class,
            ],
            'delegators' => [
                Service::class => [
                    function () {
                        return new Service();
                    },
                ],
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $this->containerFactory->__invoke($config);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUnexpectedObjectMethodAsDelegatorFactory(): void
    {
        $object = new DelegatorFactory();

        $dependencies = [
            'invokables' => [
                Service::class => Service::class,
            ],
            'delegators' => [
                Service::class => [
                    [$object, '__invoke']
                ],
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $this->containerFactory->__invoke($config);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testDoesNotAcceptDelegatorOnUndefinedServices(): void
    {
        $dependencies = [
            'delegators' => [
                'myservice' => [
                    DelegatorFactory::class,
                ],
            ],
        ];

        $builder = new ContainerBuilder();

        $config = new Config(['dependencies' => $dependencies]);
        $config->configureContainerBuilder($builder);
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

        $this->assertTrue($builder->hasDefinition(Service::class));

        $definition = $builder->getDefinition(Service::class);

        $this->assertEquals($serviceDefinition->isPublic(), $definition->isPublic());

        $builder->compile();

        $this->assertFalse($builder->has(Service::class));
        $this->assertTrue($builder->has('myalias'));

        /** @var Delegator $service */
        $service = $builder->get('myalias');

        $this->assertInstanceOf(Delegator::class, $service);
        $this->assertInstanceOf(\Closure::class, $service->callback);
    }
}
