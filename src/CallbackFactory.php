<?php

namespace JSoumelidis\SymfonyDI\Config;

use Closure;
use Psr\Container\ContainerInterface;
use Throwable;

use function class_exists;
use function get_class;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;
use function var_export;

class CallbackFactory
{
    public static function createFactoryCallback(ContainerInterface $container, string $requestedName): Closure
    {
        return function () use ($container, $requestedName) {
            /**
             * @see DelegatorTestTrait::testWithDelegatorsResolvesToInvalidClassAnExceptionIsRaisedWhenCallbackIsInvoked
             */
            try {
                return $container->get($requestedName);
            } catch (Throwable $e) {
                throw Exception\ServiceNotFoundException::create($e->getMessage(), $e);
            }
        };
    }

    /**
     * @param string $factory
     * @return mixed
     * @throws Exception\ServiceNotFoundException
     */
    public static function createServiceWithClassFactory(
        $factory,
        ContainerInterface $container,
        string $requestedName
    ) {
        if (! is_string($factory) || ! class_exists($factory) || ! is_callable($factory = new $factory())) {
            throw Exception\ServiceNotFoundException::create(sprintf(
                'Factory of type %s not found or not callable',
                is_object($factory) ? get_class($factory) : var_export($factory, true)
            ));
        }

        return $factory($container, $requestedName);
    }

    /**
     * @param string|callable $delegator
     * @throws Exception\ServiceNotFoundException
     */
    public static function createDelegatorFactoryCallback(
        $delegator,
        ContainerInterface $container,
        string $requestedName,
        callable $factoryCallback
    ): Closure {
        if (is_callable($delegator)) {
            return static::createDelegatorFactoryCallbackFromCallable(
                // @codeCoverageIgnoreStart
                $delegator,
                $container,
                $requestedName,
                // @codeCoverageIgnoreEnd
                $factoryCallback
            );
        }

        if (is_string($delegator) && class_exists($delegator)) {
            return static::createDelegatorFactoryCallbackFromName(
                // @codeCoverageIgnoreStart
                $delegator,
                $container,
                $requestedName,
                // @codeCoverageIgnoreEnd
                $factoryCallback
            );
        }

        throw Exception\ServiceNotFoundException::create(sprintf(
            'Invalid delegator for service %s',
            $requestedName
        ));
    }

    protected static function createDelegatorFactoryCallbackFromName(
        string $delegatorName,
        ContainerInterface $container,
        string $requestedName,
        callable $factoryCallback
    ): Closure {
        return function () use ($delegatorName, $container, $requestedName, $factoryCallback) {
            if (! is_callable($delegator = new $delegatorName())) {
                throw Exception\ServiceNotFoundException::create(sprintf(
                    'Delegator class %s is not callable',
                    $delegatorName
                ));
            }

            return $delegator($container, $requestedName, $factoryCallback);
        };
    }

    protected static function createDelegatorFactoryCallbackFromCallable(
        callable $callable,
        ContainerInterface $container,
        string $requestedName,
        callable $factoryCallback
    ): Closure {
        return function () use ($callable, $container, $requestedName, $factoryCallback) {
            return $callable($container, $requestedName, $factoryCallback);
        };
    }

    /**
     * @param object $delegator
     */
    public static function createDelegatorFactoryCallbackFromObjectMethodCallable(
        $delegator,
        string $method,
        ContainerInterface $container,
        string $requestedName,
        callable $callback
    ): Closure {
        return function () use ($container, $delegator, $method, $requestedName, $callback) {
            return $delegator->$method($container, $requestedName, $callback);
        };
    }
}
