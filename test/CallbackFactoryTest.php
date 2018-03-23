<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\CallbackFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Zend\ContainerConfigTest\TestAsset\Delegator;
use Zend\ContainerConfigTest\TestAsset\DelegatorFactory;
use Zend\ContainerConfigTest\TestAsset\Factory;
use Zend\ContainerConfigTest\TestAsset\Service;

class CallbackFactoryTest extends TestCase
{
    public function testCreatesContainerCallback()
    {
        $service = new \stdClass();
        $container = new Container();

        $container->set('someServiceId', $service);

        $callback = CallbackFactory::createCallback($container, 'someServiceId');

        $this->assertInstanceOf(\Closure::class, $callback);
        $this->assertEquals($service, $callback());
    }

    public function testCreatesFactoryCallbackFromCallable()
    {
        $container = new Container();
        $callable = function (ContainerInterface $container, $requestedName) {
            if ($requestedName === 'myservice') {
                return new \stdClass();
            }

            throw new \UnexpectedValueException();
        };

        $callback = CallbackFactory::createFactoryCallback($callable, $container, 'myservice');

        $this->assertInstanceOf(\Closure::class, $callback);
        $this->assertThat($callback(), $this->isType('object'));
    }

    public function testCreatesFactoryCallbackFromInvokableClassName()
    {
        $container = new Container();

        $callback = CallbackFactory::createFactoryCallback(
            Factory::class,
            $container,
            'myservice'
        );

        $this->assertInstanceOf(\Closure::class, $callback);
        $this->assertInstanceOf(Service::class, $callback());
    }

    public function testCreatesDelegatorFactoryCallbackFromCallable()
    {
        $container = new Container();
        $factoryCallback = function () {
            return new \stdClass();
        };

        $callable = function (ContainerInterface $container, $requestedName, callable $factory) {
            if ($requestedName === 'myservice') {
                $origin = $factory();
                return (object)['origin' => $origin];
            }

            throw new \UnexpectedValueException();
        };

        $callback = CallbackFactory::createDelegatorFactoryCallback(
            $callable,
            $container,
            'myservice',
            $factoryCallback
        );

        $this->assertInstanceOf(\Closure::class, $callback);
        $this->assertInstanceOf(\stdClass::class, $object = $callback());
        $this->assertInstanceOf(\stdClass::class, $object->origin);
    }

    public function testCreatesDelegatorFactoryCallbackFromInvokableClassName()
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
