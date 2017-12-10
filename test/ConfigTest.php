<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;
use JSoumelidisTest\SymfonyDI\Config\Factory\Delegator1;
use JSoumelidisTest\SymfonyDI\Config\Factory\Delegator2;
use JSoumelidisTest\SymfonyDI\Config\Factory\Delegator3;
use JSoumelidisTest\SymfonyDI\Config\Factory\InvokableServiceFactory;
use JSoumelidisTest\SymfonyDI\Config\Factory\ServiceWithDependencyFactory;
use JSoumelidisTest\SymfonyDI\Config\Service\InvokableService;
use JSoumelidisTest\SymfonyDI\Config\Service\ServiceWithDependency;
use JSoumelidisTest\SymfonyDI\Config\Service\Wrapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnexpectedValueException;

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

    public function testInjectsConfiguration()
    {
        $config = [
            'foo' => 'bar',
        ];

        $container = $this->containerFactory->__invoke(new Config($config));

        $this->assertTrue($container->has('config'));
        $this->assertInstanceOf(\ArrayObject::class, $container->get('config'));
        $this->assertSame($config, $container->get('config')->getArrayCopy());
    }

    public function testServices()
    {
        $dependencies = [
            'services' => [
                InvokableService::class => $service = new InvokableService(),
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $container = $this->containerFactory->__invoke($config);

        $this->assertTrue($container->has(InvokableService::class));
        $this->assertEquals($service, $container->get(InvokableService::class));
    }

    public function testServicesAsSyntheticRegister()
    {
        $dependencies = [
            'services' => [
                InvokableService::class => $service = new InvokableService(),
            ],
        ];

        $config = new Config(['dependencies' => $dependencies], true);
        /** @var ContainerBuilder $container */
        $container = $this->containerFactory->__invoke($config);

        $this->assertTrue($container->has(InvokableService::class));
        $this->assertTrue($container->getDefinition(InvokableService::class)->isSynthetic());
    }

    /**
     * @expectedException \Exception
     */
    public function testServicesAsSyntheticCannotBeFetched()
    {
        $dependencies = [
            'services' => [
                InvokableService::class => $service = new InvokableService(),
            ],
        ];

        $config = new Config(['dependencies' => $dependencies], true);

        $this->containerFactory->__invoke($config)->get(InvokableService::class);
    }

    public function testInvokableService()
    {
        $dependencies = [
            'invokables' => [
                'invokableService' => InvokableService::class,
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $container = $this->containerFactory->__invoke($config);

        $this->assertTrue($container->has('invokableService'));
        $this->assertInstanceOf(InvokableService::class, $container->get('invokableService'));
    }

    public function testFactories()
    {
        require __DIR__ . '/Factory/FactoryFunctions.php';

        $dependencies = [
            'factories' => [
                'phpFunctionAsFactory' =>
                    __NAMESPACE__ . '\Factory\phpFunctionAsFactory',

                'staticMethodArrayAsFactory' =>
                    [InvokableServiceFactory::class, 'create'],

                'staticMethodStringAsFactory' =>
                    InvokableServiceFactory::class . '::create',

                'classNameAsFactory' =>
                    InvokableServiceFactory::class,
            ]
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $container = $this->containerFactory->__invoke($config);

        $this->assertTrue($container->has('phpFunctionAsFactory'));
        $this->assertInstanceOf(InvokableService::class, $container->get('phpFunctionAsFactory'));

        $this->assertTrue($container->has('staticMethodArrayAsFactory'));
        $this->assertInstanceOf(InvokableService::class, $container->get('staticMethodArrayAsFactory'));

        $this->assertTrue($container->has('staticMethodStringAsFactory'));
        $this->assertInstanceOf(InvokableService::class, $container->get('staticMethodStringAsFactory'));

        $this->assertTrue($container->has('classNameAsFactory'));
        $this->assertInstanceOf(InvokableService::class, $container->get('classNameAsFactory'));
    }

    public function testFactoryWithDependencies()
    {
        $dependencies = [
            'services' => [
                'dependency' => $dependency = new InvokableService(),
            ],
            'factories' => [
                ServiceWithDependency::class => ServiceWithDependencyFactory::class,
            ]
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $container = $this->containerFactory->__invoke($config);

        $this->assertTrue($container->has(ServiceWithDependency::class));
        $this->assertInstanceOf(
            ServiceWithDependency::class,
            $object = $container->get(ServiceWithDependency::class)
        );

        /**
         * Check $dependency has been injected by the factory
         *
         * @var ServiceWithDependency $object
         */
        $this->assertEquals($object->getDependency(), $dependency);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUnexpectedClosureAsFactory()
    {
        $dependencies = [
            'factories' => [
                'invalidFactoryService' => function () {
                    return new InvokableService();
                },
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $this->containerFactory->__invoke($config);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUnexpectedObjectMethodAsFactory()
    {
        $object = new ServiceWithDependencyFactory();

        $dependencies = [
            'factories' => [
                'invalidFactoryService' => [$object, '__invoke'],
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $this->containerFactory->__invoke($config);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUnexpectedClosureAsDelegatorFactory()
    {
        $dependencies = [
            'services' => [
                'randomService' => new InvokableService(),
            ],
            'delegators' => [
                'randomService' => [
                    function () {
                        return new InvokableService();
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
    public function testInvalidClassNameAsFactory()
    {
        $dependencies = [
            'factories' => [
                InvokableService::class => 'someNonExistentClassName',
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $this->containerFactory->__invoke($config);
    }

    public function testDelegators()
    {
        require __DIR__ . '/Factory/FactoryFunctions.php';

        $dependencies = [
            'services' => [
                'dependency' => $dependency = new InvokableService(),
            ],
            'factories' => [
                'objectWithDependency' => ServiceWithDependencyFactory::class,
            ],
            'delegators' => [
                'objectWithDependency' => [
                    //Invokable class name as delegator
                    //returns a ServiceWithDependency object
                    Delegator1::class,
                    //static method callable string as delegator
                    //returns a Wrapper object
                    Delegator2::class . '::create',
                    //static method callable array as delegator
                    //returns a ServiceWithDependency object
                    [Delegator3::class, 'create'],
                    //php function as delegator
                    //returns a Wrapper object
                    __NAMESPACE__ . '\Factory\phpFunctionAsDelegatorFactory',
                ],
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $container = $this->containerFactory->__invoke($config);

        $this->assertTrue($container->has('objectWithDependency'));

        //Returned object should be the last delegator's return value
        $wrapper1 = $container->get('objectWithDependency');
        $this->assertInstanceOf(Wrapper::class, $wrapper1);

        /** @var Wrapper $wrapper1 */
        $serviceWithDependency1 = $wrapper1->getService();
        $this->assertInstanceOf(ServiceWithDependency::class, $serviceWithDependency1);

        /** @var ServiceWithDependency $serviceWithDependency1 */
        $wrapper2 = $serviceWithDependency1->getDependency();
        $this->assertInstanceOf(Wrapper::class, $wrapper2);

        /** @var Wrapper $wrapper2 */
        $serviceWithDependency2 = $wrapper2->getService();
        $this->assertInstanceOf(ServiceWithDependency::class, $serviceWithDependency2);

        /** @var ServiceWithDependency $serviceWithDependency2 */
        $object = $serviceWithDependency2->getDependency();
        $this->assertInstanceOf(ServiceWithDependency::class, $object);

        /** @var ServiceWithDependency $object */
        $this->assertEquals($dependency, $object->getDependency());
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testUnexpectedObjectMethodAsDelegatorFactory()
    {
        $object = new Delegator1();

        $dependencies = [
            'invokables' => [
                InvokableService::class => InvokableService::class,
            ],
            'delegators' => [
                InvokableService::class => [
                    [$object, '__invoke']
                ],
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $this->containerFactory->__invoke($config);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testInvalidClassNameAsDelegatorFactory()
    {
        $dependencies = [
            'invokables' => [
                InvokableService::class => InvokableService::class,
            ],
            'delegators' => [
                InvokableService::class => [
                    'someNonExistentClassName'
                ],
            ],
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $this->containerFactory->__invoke($config);
    }

    public function testAlias()
    {
        $dependencies = [
            'invokables' => [
                InvokableService::class => InvokableService::class,
            ],
            'aliases' => [
                'alias1' => InvokableService::class,
                'alias2' => 'alias1',
            ]
        ];

        $config = new Config(['dependencies' => $dependencies]);
        $container = $this->containerFactory->__invoke($config);

        $this->assertTrue($container->has('alias1'));
        $this->assertInstanceOf(InvokableService::class, $container->get('alias1'));

        $this->assertTrue($container->has('alias2'));
        $this->assertInstanceOf(InvokableService::class, $container->get('alias2'));
    }
}
