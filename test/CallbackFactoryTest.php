<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\CallbackFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Laminas\ContainerConfigTest\TestAsset\Delegator;
use Laminas\ContainerConfigTest\TestAsset\DelegatorFactory;

class CallbackFactoryTest extends TestCase
{
    public function testCreatesContainerCallback(): void
    {
        $service = new \stdClass();
        $container = new Container();

        $container->set('someServiceId', $service);

        $callback = CallbackFactory::createFactoryCallback($container, 'someServiceId');

        self::assertInstanceOf(\Closure::class, $callback);
        self::assertEquals($service, $callback());
    }

    public function testCreatesDelegatorFactoryCallbackFromInvokableClassName(): void
    {
        $container = new Container();
        $factoryCallback = function () {
            return new \stdClass();
        };

        $callback = CallbackFactory::createDelegatorFactoryCallback(
            DelegatorFactory::class,
            $container,
            'myservice',
            $factoryCallback
        );

        self::assertInstanceOf(\Closure::class, $callback);
        self::assertInstanceOf(Delegator::class, $object = $callback());
        self::assertInstanceOf(\Closure::class, $object->callback);
    }
}
