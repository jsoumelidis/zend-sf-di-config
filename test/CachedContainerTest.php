<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Laminas\ContainerConfigTest\AbstractMezzioContainerConfigTest;
use Laminas\ContainerConfigTest\TestAsset\Delegator;
use Laminas\ContainerConfigTest\TestAsset\DelegatorFactory;
use Laminas\ContainerConfigTest\TestAsset\Factory;
use Laminas\ContainerConfigTest\TestAsset\Service;

/**
 * Test that a dumped container configuration
 * passes all normal tests when booted
 */
class CachedContainerTest extends AbstractMezzioContainerConfigTest
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
            'class' => $containerClass = "ContainerTest_" . uniqid('', false),
        ]));

        require_once($this->containerCacheFile);

        $container = new $containerClass;

        $config->setSyntheticServices($container);

        return $container;
    }

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->containerCacheFile = tempnam(sys_get_temp_dir(), 'sc_');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
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
}
