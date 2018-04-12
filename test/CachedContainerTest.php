<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Zend\ContainerConfigTest\AbstractExpressiveContainerConfigTest;
use Zend\ContainerConfigTest\TestAsset\Delegator;
use Zend\ContainerConfigTest\TestAsset\DelegatorFactory;
use Zend\ContainerConfigTest\TestAsset\Factory;
use Zend\ContainerConfigTest\TestAsset\Service;

/**
 * Test that a dumped container configuration
 * passes all normal tests when booted
 */
class CachedContainerTest extends AbstractExpressiveContainerConfigTest
{
    /**
     * @var string
     */
    protected $containerCacheFile;

    protected function createContainer(array $config) : ContainerInterface
    {
        $factory = new ContainerFactory();
        $config = new Config(['dependencies' => $config], true);

        $container = $factory($config);

        $container->compile();
        file_put_contents($this->containerCacheFile, (new PhpDumper($container))->dump([
            'class' => $containerClass = "ContainerTest_" . uniqid(),
        ]));

        require_once($this->containerCacheFile);

        $container = new $containerClass;

        $config->setSyntheticServices($container);

        return $container;
    }

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->containerCacheFile = tempnam(sys_get_temp_dir(), 'sc_');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        @unlink($this->containerCacheFile);
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

        $this->assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);
        $this->assertInstanceOf(Service::class, $object);
        $this->assertInstanceOf(ContainerInterface::class, $object->injected[0]);
    }

    public function testObjectMethodCallableAsFactory(): void
    {
        $dependencies = [
            'factories' => [
                Service::class => [new Factory(), '__invoke'],
            ],
        ];

        $container = $this->createContainer($dependencies);

        $this->assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);

        $this->assertInstanceOf(Service::class, $object);
    }

    public function testObjectAsFactory(): void
    {
        $dependencies = [
            'factories' => [
                Service::class => new Factory(),
            ],
        ];

        $container = $this->createContainer($dependencies);

        $this->assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);
        $this->assertInstanceOf(Service::class, $object);
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

        $this->assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);

        $this->assertInstanceOf(Delegator::class, $object);
        $this->assertInstanceOf(\Closure::class, $callback = $object->callback);
        $this->assertInstanceOf(Service::class, $callback());
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

        $this->assertTrue($container->has(Service::class));

        $object = $container->get(Service::class);

        $this->assertInstanceOf(Delegator::class, $object);
        $this->assertInstanceOf(\Closure::class, $callback = $object->callback);
        $this->assertInstanceOf(Service::class, $callback());
    }
}
