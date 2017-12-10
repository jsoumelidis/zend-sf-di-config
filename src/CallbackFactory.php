<?php

namespace JSoumelidis\SymfonyDI\Config;

use Psr\Container\ContainerInterface;
use UnexpectedValueException;

class CallbackFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     *
     * @return \Closure
     */
    public static function createCallback(ContainerInterface $container, $requestedName)
    {
        return function () use ($container, $requestedName) {
            return $container->get($requestedName);
        };
    }

    /**
     * @param string|callable $factory
     * @param ContainerInterface $container
     * @param string $requestedName
     *
     * @return \Closure
     */
    public static function createFactoryCallback(
        $factory,
        ContainerInterface $container,
        $requestedName
    ) {
        if (is_callable($factory)) {
            return static::createFactoryCallbackFromCallable($factory, $container, $requestedName);
        }

        if (is_string($factory) && class_exists($factory)) {
            return static::createFactoryCallbackFromName($factory, $container, $requestedName);
        }

        throw new UnexpectedValueException('Expected a callable or a valid class name');
    }

    /**
     * @param callable $callable
     * @param ContainerInterface $container
     * @param string $requestedName
     *
     * @return \Closure
     */
    protected static function createFactoryCallbackFromCallable(
        callable $callable,
        ContainerInterface $container,
        $requestedName
    ) {
        return function () use ($callable, $container, $requestedName) {
            return $callable($container, $requestedName);
        };
    }

    /**
     * @param string $factory
     * @param ContainerInterface $container
     * @param string $requestedName
     *
     * @return \Closure
     */
    protected static function createFactoryCallbackFromName(
        $factory,
        ContainerInterface $container,
        $requestedName
    ) {
        return function () use ($factory, $container, $requestedName) {
            $factory = new $factory;
            return $factory($container, $requestedName);
        };
    }

    /**
     * @param string|callable $delegator
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param callable $factoryCallback
     *
     * @return \Closure
     */
    public static function createDelegatorFactoryCallback(
        $delegator,
        ContainerInterface $container,
        $requestedName,
        callable $factoryCallback
    ) {
        if (is_callable($delegator)) {
            return static::createDelegatorFactoryCallbackFromCallable(
                $delegator,
                $container,
                $requestedName,
                $factoryCallback
            );
        }

        if (is_string($delegator) && class_exists($delegator)) {
            return static::createDelegatorFactoryCallbackFromName(
                $delegator,
                $container,
                $requestedName,
                $factoryCallback
            );
        }

        throw new UnexpectedValueException('Expected a callable or a valid class name');
    }

    /**
     * @param string $delegatorName
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param callable $factoryCallback
     *
     * @return \Closure
     */
    public static function createDelegatorFactoryCallbackFromName(
        $delegatorName,
        ContainerInterface $container,
        $requestedName,
        callable $factoryCallback
    ) {
        return function () use ($delegatorName, $container, $requestedName, $factoryCallback) {
            $delegator = new $delegatorName;
            return $delegator($container, $requestedName, $factoryCallback);
        };
    }

    /**
     * @param callable $callable
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param callable $factoryCallback
     *
     * @return \Closure
     */
    protected static function createDelegatorFactoryCallbackFromCallable(
        callable $callable,
        ContainerInterface $container,
        $requestedName,
        callable $factoryCallback
    ) {
        return function () use ($callable, $container, $requestedName, $factoryCallback) {
            return $callable($container, $requestedName, $factoryCallback);
        };
    }
}
