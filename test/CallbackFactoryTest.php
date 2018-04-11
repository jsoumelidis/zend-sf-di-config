<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\CallbackFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Zend\ContainerConfigTest\TestAsset\Delegator;
use Zend\ContainerConfigTest\TestAsset\DelegatorFactory;

class CallbackFactoryTest extends TestCase
{
    public function testCreatesContainerCallback(): void
    {
        $service = new \stdClass();
        $container = new Container();

        $container->set('someServiceId', $service);

        $callback = CallbackFactory::createCallback($container, 'someServiceId');

        $this->assertInstanceOf(\Closure::class, $callback);
        $this->assertEquals($service, $callback());
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

        $this->assertInstanceOf(\Closure::class, $callback);
        $this->assertInstanceOf(Delegator::class, $object = $callback());
        $this->assertInstanceOf(\Closure::class, $object->callback);
    }
}
