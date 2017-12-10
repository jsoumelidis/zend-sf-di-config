<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\CallbackFactory;
use JSoumelidisTest\SymfonyDI\Config\Factory\Delegator1;
use JSoumelidisTest\SymfonyDI\Config\Factory\InvokableServiceFactory;
use JSoumelidisTest\SymfonyDI\Config\Service\InvokableService;
use JSoumelidisTest\SymfonyDI\Config\Service\ServiceWithDependency;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;

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
            InvokableServiceFactory::class,
            $container,
            'myservice'
        );

        $this->assertInstanceOf(\Closure::class, $callback);
        $this->assertInstanceOf(InvokableService::class, $callback());
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testCreateFactoryCallbackThrowsExceptionForInvalidFactory()
    {
        $container = new Container();

        CallbackFactory::createFactoryCallback('someInvalidFactory', $container, 'someName');
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
                return new ServiceWithDependency($origin);
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
        $this->assertInstanceOf(ServiceWithDependency::class, $object = $callback());
        /** @var ServiceWithDependency $object */
        $this->assertThat($object->getDependency(), $this->isType('object'));
    }

    public function testCreatesDelegatorFactoryCallbackFromInvokableClassName()
    {
        $container = new Container();
        $factoryCallback = function () {
            return new \stdClass();
        };

        $callback = CallbackFactory::createDelegatorFactoryCallback(
            Delegator1::class,
            $container,
            'myservice',
            $factoryCallback
        );

        $this->assertInstanceOf(\Closure::class, $callback);
        $this->assertInstanceOf(ServiceWithDependency::class, $object = $callback());
        /** @var ServiceWithDependency $object */
        $this->assertThat($object->getDependency(), $this->isType('object'));
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testCreateDelegatorFactoryCallbackThrowsExceptionForInvalidFactory()
    {
        $container = new Container();
        $factoryCallback = function () {
        };

        CallbackFactory::createDelegatorFactoryCallback(
            'someInvalidDelegator',
            $container,
            'someName',
            $factoryCallback
        );
    }
}
