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
     * @return \Closure
     *
     * @throws UnexpectedValueException
     */
    public static function createFactoryCallback(
        $factory,
        ContainerInterface $container,
        string $requestedName
    ): \Closure {
        if (is_callable($factory)) {
            return static::createFactoryCallbackFromCallable($factory, $container, $requestedName);
        }

        if (is_string($factory)) {
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
        string $requestedName
    ): \Closure {
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
        string $factory,
        ContainerInterface $container,
        string $requestedName
    ): \Closure {
        return function () use ($factory, $container, $requestedName) {
            if (! class_exists($factory) || ! is_callable($factory = new $factory)) {
                throw Exception\ServiceNotFoundException::create(sprintf(
                    'Factory class %s not found or not callable',
                    is_string($factory) ? $factory : get_class($factory)
                ));
            }

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
            //PHP 5.6 fix
            return call_user_func($callable, $container, $requestedName, $factoryCallback);
        };
    }
}
