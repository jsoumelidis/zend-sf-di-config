<?php

namespace JSoumelidis\SymfonyDI\Config;

use Psr\Container\ContainerInterface;

class CallbackFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     *
     * @return \Closure
     *
     * @throws Exception\ServiceNotFoundException
     */
    public static function createCallback(ContainerInterface $container, string $requestedName): \Closure
    {
        return function () use ($container, $requestedName) {
            /**
             * @see DelegatorTestTrait::testWithDelegatorsResolvesToInvalidClassAnExceptionIsRaisedWhenCallbackIsInvoked
             */
            try {
                return $container->get($requestedName);
            } catch (\Throwable $e) {
                throw Exception\ServiceNotFoundException::create($e->getMessage(), $e);
            }
        };
    }

    /**
     * @param string|callable $factory
     * @param ContainerInterface $container
     * @param string $requestedName
     *
     * @return mixed
     *
     * @throws Exception\ServiceNotFoundException
     */
    public static function createFactoryCallback(
        $factory,
        ContainerInterface $container,
        string $requestedName
    ) {
        if (is_callable($factory)) {
            return $factory($container, $requestedName);
        }

        if (! is_string($factory) || ! class_exists($factory) || ! is_callable($factory = new $factory)) {
            throw Exception\ServiceNotFoundException::create(sprintf(
                'Factory class %s not found or not callable',
                is_object($factory) ? get_class($factory) : var_export($factory, true)
            ));
        }

        return $factory($container, $requestedName);
    }

    /**
     * @param string|callable $delegator
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param callable $factoryCallback
     *
     * @return \Closure
     *
     * @throws Exception\ServiceNotFoundException
     */
    public static function createDelegatorFactoryCallback(
        $delegator,
        ContainerInterface $container,
        string $requestedName,
        callable $factoryCallback
    ): \Closure {
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

    /**
     * @param string $delegatorName
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param callable $factoryCallback
     *
     * @return \Closure
     */
    protected static function createDelegatorFactoryCallbackFromName(
        string $delegatorName,
        ContainerInterface $container,
        string $requestedName,
        callable $factoryCallback
    ): \Closure {
        return function () use ($delegatorName, $container, $requestedName, $factoryCallback) {
            $delegator = new $delegatorName;

            if (! is_callable($delegator)) {
                throw Exception\ServiceNotFoundException::create(sprintf(
                    'Delegator class %s is not callable',
                    $delegatorName
                ));
            }

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
        string $requestedName,
        callable $factoryCallback
    ): \Closure {
        return function () use ($callable, $container, $requestedName, $factoryCallback) {
            $callable($container, $requestedName, $factoryCallback);
        };
    }
}
