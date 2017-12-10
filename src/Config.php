<?php

namespace JSoumelidis\SymfonyDI\Config;

use Closure;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use UnexpectedValueException;

class Config implements ConfigInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var bool
     */
    private $servicesAsSynthetic;

    public function __construct(array $config, $servicesAsSynthetic = false)
    {
        $this->config = $config;
        $this->servicesAsSynthetic = (bool)$servicesAsSynthetic;
    }

    public function configureContainerBuilder(ContainerBuilder $builder)
    {
        $config = new \ArrayObject($this->config, \ArrayObject::ARRAY_AS_PROPS);
        $builder->set('config', $config);

        if (isset($this->config['dependencies'])
            && is_array($this->config['dependencies'])
        ) {
            $dependencies = $this->config['dependencies'];

            //Inject known services
            if (! empty($dependencies['services']) && is_array($dependencies['services'])) {
                foreach ($dependencies['services'] as $name => $object) {
                    if ($this->servicesAsSynthetic) {
                        $type = is_object($object) ? get_class($object) : gettype($object);
                        $builder->register($name, $type)->setSynthetic(true);
                    } else {
                        $builder->set($name, $object);
                    }
                }
            }

            //Inject invokable services
            if (! empty($dependencies['invokables']) && is_array($dependencies['invokables'])) {
                foreach ($dependencies['invokables'] as $name => $invokable) {
                    $builder->register($name, $invokable);
                }
            }

            //Inject factories
            if (! empty($dependencies['factories']) && is_array($dependencies['factories'])) {
                foreach ($dependencies['factories'] as $name => $factory) {
                    $this->injectFactory($name, $factory, $builder);
                }
            }

            //Inject aliases
            if (! empty($dependencies['aliases']) && is_array($dependencies['aliases'])) {
                foreach ($dependencies['aliases'] as $alias => $name) {
                    $builder->setAlias($alias, $name);
                }
            }

            //Inject delegators
            if (! empty($dependencies['delegators']) && is_array($dependencies['delegators'])) {
                foreach ($dependencies['delegators'] as $name => $delegatorNames) {
                    if (is_array($delegatorNames) && $delegatorNames) {
                        $this->injectDelegators($name, $delegatorNames, $builder);
                    }
                }
            }
        }
    }

    /**
     * Creates an internal service id
     *
     * @param string $name A name to create the id for
     *
     * @return string
     */
    protected function zendSmSfDiBridgeCreateId($name)
    {
        return "smsfbridge.{$name}";
    }

    /**
     * Injects definition for a ServiceManager factory entry
     *
     * @param string $id Service name to create a factory for
     * @param string|callable $factory The factory definition
     * @param ContainerBuilder $builder The builder that factory will be injected
     *
     * @return void
     */
    protected function injectFactory($id, $factory, ContainerBuilder $builder)
    {
        if (is_callable($factory)) {
            if ($factory instanceof Closure) {
                throw new UnexpectedValueException(
                    "This bridge does not support Closures as factories ({$id})"
                );
            }

            if (is_array($factory) && ! is_string($factory[0])) {
                throw new UnexpectedValueException(
                    "This bridge supports only php named functions or static methods as callable factories ({$id})"
                );
            }

            $builder->register($id, $id)
                ->setFactory($factory)
                //Zend ServiceManager FactoryInterface arguments
                ->setArguments([new Reference('service_container'), $id]);

            return;
        }

        if (! is_string($factory) || ! class_exists($factory)) {
            throw new UnexpectedValueException(
                "This bridge supports callables or invokable class names as factories"
            );
        }

        $factoryCallbackId = $this->zendSmSfDiBridgeCreateId(
            "{$id}.{$factory}.factory.callback"
        );

        //Create a callback that - when called - will return an instance of $factory class
        $builder->register($factoryCallbackId, Closure::class)
            ->setPublic(false)
            ->setFactory([CallbackFactory::class, 'createFactoryCallback'])
            //Zend ServiceManager FactoryInterface arguments */
            ->setArguments(
                [
                    $factory,
                    /* Zend ServiceManager FactoryInterface arguments */
                    new Reference('service_container'),
                    $id
                ]
            );

        $builder->register($id, $id)
            ->setFactory([new Reference($factoryCallbackId), '__invoke']);
    }

    /**
     * Inject definitions for a service's delegators
     *
     * @param string $id Service id that delegators will be injected for
     * @param array $delegators Delegators
     * @param ContainerBuilder $builder The builder that delegators will be injected
     *
     * @return void
     */
    protected function injectDelegators($id, array $delegators, ContainerBuilder $builder)
    {
        if ($builder->hasAlias($id)) {
            throw new UnexpectedValueException(
                "Delegators for aliases are not supported ({$id})"
            );
        }

        if (! $builder->hasDefinition($id)) {
            throw new UnexpectedValueException(
                "Delegators for undefined/runtime services are not supported ({$id})"
            );
        }

        $definition = $builder->getDefinition($id);

        if ($definition->isSynthetic()) {
            throw new UnexpectedValueException(
                "Delegators for synthetic services are not supported ({$id})"
            );
        }

        //we will rename the original service's id to something 'private'
        $builder->removeDefinition($id);

        //Create an "internal" definition and make it public
        //so it can be fetched later by the $factoryCallbackId Closure
        $originDefinition = clone $definition;
        $originDefinition->setPublic(true);

        $originId = $this->zendSmSfDiBridgeCreateId("{$id}.origin");
        $builder->setDefinition($originId, $originDefinition);

        //register a closure that, when fetched and invoked, returns
        //the original service from the container
        //this Closure object will be passed as 3rd argument to the 1st delegator
        $factoryCallbackId = $this->zendSmSfDiBridgeCreateId("{$id}.factory.callback");
        $builder
            ->register($factoryCallbackId, Closure::class)
            ->setPublic(false)
            ->setFactory([CallbackFactory::class, 'createCallback'])
            ->setArguments([new Reference('service_container'), $originId]);

        for ($delegator = reset($delegators);
             $delegator !== false;
             $delegator = next($delegators), $factoryCallbackId = $delegatorFactoryCallbackId) {
            $delegatorName = $delegator;

            if (is_callable($delegator)) {
                //Static method as delegator
                if (is_array($delegator) && is_string($delegator[0]) && is_string($delegator[1])) {
                    $delegatorName = "{$delegator[0]}::{$delegator[1]}";
                } elseif (! is_string($delegator)) {
                    throw new UnexpectedValueException(
                        "This bridge supports only PHP functions or static methods as callable delegator factories"
                    );
                }
            } elseif (! is_string($delegator) || ! class_exists($delegator)) {
                throw new UnexpectedValueException(
                    "This bridge supports callables or invokable class names as delegator factories"
                );
            }

            $delegatorFactoryCallbackId = $this->zendSmSfDiBridgeCreateId(
                "{$factoryCallbackId}.delegator.{$delegatorName}.callback"
            );

            $builder
                ->register($delegatorFactoryCallbackId, Closure::class)
                ->setPublic(false)
                ->setFactory([CallbackFactory::class, 'createDelegatorFactoryCallback'])
                ->setArguments(
                    [
                        $delegator,
                        new Reference('service_container'),
                        $id,
                        new Reference($factoryCallbackId)
                    ]
                );
        }

        //Finally, register the last Closure as factory for the service
        //This action removes any previous alias registered for $id
        $builder
            ->register($id, $definition->getClass())
            ->setPublic($definition->isPublic())
            ->setShared($definition->isShared())
            ->setFactory([new Reference($factoryCallbackId), '__invoke']);
    }
}
