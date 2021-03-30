<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use Closure;
use JSoumelidis\SymfonyDI\Config\CallbackFactory;
use Laminas\ContainerConfigTest\TestAsset\Delegator;
use Laminas\ContainerConfigTest\TestAsset\DelegatorFactory;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\Container;

class CallbackFactoryTest extends TestCase
{
    public function testCreatesContainerCallback(): void
    {
        $service   = new stdClass();
        $container = new Container();

        $container->set('someServiceId', $service);

        $callback = CallbackFactory::createFactoryCallback($container, 'someServiceId');

        self::assertInstanceOf(Closure::class, $callback);
        self::assertEquals($service, $callback());
    }

    public function testCreatesDelegatorFactoryCallbackFromInvokableClassName(): void
    {
        $container       = new Container();
        $factoryCallback = function () {
            return new stdClass();
        };

        $callback = CallbackFactory::createDelegatorFactoryCallback(
            DelegatorFactory::class,
            $container,
            'myservice',
            $factoryCallback
        );

        self::assertInstanceOf(Closure::class, $callback);
        self::assertInstanceOf(Delegator::class, $object = $callback());
        self::assertInstanceOf(Closure::class, $object->callback);
    }
}
