<?php

namespace JSoumelidis\SymfonyDI\Config;

use Closure;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use UnexpectedValueException;
use Zend\ContainerConfigTest\DelegatorTestTrait;

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

    /**
     * Config constructor.
     *
     * @param array $config
     * @param bool $servicesAsSynthetic
     */
    public function __construct(array $config, bool $servicesAsSynthetic = false)
    {
        $this->config = $config;
        $this->servicesAsSynthetic = $servicesAsSynthetic;
    }

    /**
     * @param Container $container
     */
    public function setSyntheticServices(Container $container): void
    {
        $container->set('config', new \ArrayObject($this->config, \ArrayObject::ARRAY_AS_PROPS));

        if (isset($this->config['dependencies']) && is_array($this->config['dependencies'])) {
            $dependencies = $this->config['dependencies'];

            if (! empty($dependencies['services']) && is_array($dependencies['services'])) {
                foreach ($dependencies['services'] as $name => $object) {
                    $container->set($name, $object);
                }
            }

            //set factory objects
            if (! empty($dependencies['factories']) && is_array($dependencies['factories'])) {
                foreach ($dependencies['factories'] as $name => $factory) {
                    if (is_object($factory) || (is_array($factory) && is_object($factory[0]))) {
                        $factoryObjectServiceId = $this->getFactoryObjectServiceId($name);
                        $factoryObject = is_object($factory) ? $factory : $factory[0];

                        $container->set($factoryObjectServiceId, $factoryObject);
                    }
                }
            }

            //set delegator objects
            if (! empty($dependencies['delegators']) && is_array($dependencies['delegators'])) {
                foreach ($dependencies['delegators'] as $name => $delegators) {
                    if (is_array($delegators)) {
                        foreach ($delegators as $index => $delegator) {
                            if (is_object($delegator) || (is_array($delegator) && is_object($delegator[0]))) {
                                $delegatorObjectServiceId = $this->getDelegatorObjectServiceId($name, $index);
                                $delegatorObject = is_object($delegator) ? $delegator : $delegator[0];

                                $container->set($delegatorObjectServiceId, $delegatorObject);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function configureContainerBuilder(ContainerBuilder $builder)
    {
        if ($this->servicesAsSynthetic) {
            $builder->register('config', \ArrayObject::class)
                ->setSynthetic(true)
                ->setPublic(true);
        } else {
            $config = new \ArrayObject($this->config, \ArrayObject::ARRAY_AS_PROPS);
            $builder->set('config', $config);
        }

        if (isset($this->config['dependencies']) && is_array($this->config['dependencies'])) {
            $dependencies = $this->config['dependencies'];

            //Inject known services
            if (! empty($dependencies['services']) && is_array($dependencies['services'])) {
                foreach ($dependencies['services'] as $name => $object) {
                    if ($this->servicesAsSynthetic) {
                        $type = is_object($object) ? get_class($object) : gettype($object);
                        $builder->register($name, $type)->setSynthetic(true)->setPublic(true);
                    } else {
                        $builder->set($name, $object);
                    }
                }
            }

            //Inject invokable services
            if (! empty($dependencies['invokables']) && is_array($dependencies['invokables'])) {
                foreach ($dependencies['invokables'] as $name => $invokable) {
                    /** @see \Zend\ContainerConfigTest\InvokableTestTrait::testCanSpecifyInvokableWithoutKey */
                    if (! is_string($name)) {
                        $name = $invokable;
                    }

                    //As Zend team proposes: all invokables must be registered by their actual class name

                    /**
                     * Workaround for
                     * @see InvokableTestTrait::testFetchingNonExistingInvokableServiceResultsInException
                     */
                    if (! class_exists($invokable)) {
                        $this->registerWrapperForService($invokable, $builder);
                    } else {
                        $builder->register($invokable, $invokable)->setPublic(true);
                    }

                    /**
                     * If service name does not match the class name, we create an alias
                     *
                     * @see InvokableTestTrait::testCanFetchInvokableByBothAliasAndClassName
                     */
                    if ($name !== $invokable) {
                        $builder->setAlias($name, $invokable)->setPublic(true);
                    }
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
                foreach ($dependencies['aliases'] as $alias => $target) {
                    //Compiler: Aliasing an actual service requires synthetic definition
                    if (isset($dependencies['services'][$target]) && ! $builder->hasDefinition($target)) {
                        $builder->register($target, is_object($target) ? get_class($target) : gettype($target))
                            ->setSynthetic(true)
                            ->setPublic(true);
                    }

                    $builder->setAlias($alias, $target)->setPublic(true);
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
    protected function zendSmSfDiBridgeCreateId(string $name): string
    {
        return "smsfbridge.{$name}";
    }

    /**
     * Injects definition for a ServiceManager factory entry
     *
     * @param string $id Service name to create a factory for
     * @param string|callable|array|object $factory The factory definition
     * @param ContainerBuilder $builder The builder that factory will be injected
     *
     * @return void
     */
    protected function injectFactory(string $id, $factory, ContainerBuilder $builder): void
    {
        /**
         * support for invokable (object) and [(object), 'method']
         *
         * - comply with Definition::setFactory() documentation
         * - allow dumping/caching container's configuration using synthetic services
         */
        if (is_object($factory) || (is_array($factory) && is_object($factory[0]) && is_string($factory[1]))) {
            //register factory object as service
            $factoryObjectServiceId = $this->getFactoryObjectServiceId($id);
            $factoryObject = is_object($factory) ? $factory : $factory[0];

            if ($this->servicesAsSynthetic) {
                $builder->register($factoryObjectServiceId, get_class($factoryObject))
                    ->setSynthetic(true)
                    ->setPublic(true);
            } else {
                $builder->set($factoryObjectServiceId, $factoryObject);
            }

            $method = is_object($factory) ? '__invoke' : $factory[1];

            $builder->register($id, $id)
                ->setPublic(true)
                ->setFactory([new Reference($factoryObjectServiceId), $method])
                ->setArguments([new Reference('service_container'), $id]);

            return;
        }

        //static method or php named function
        if (is_callable($factory)) {
            $builder->register($id, $id)
                ->setPublic(true)
                ->setFactory($factory)
                ->setArguments([new Reference('service_container'), $id]);

            return;
        }

        //class name
        $builder->register($id, $id)
            ->setPublic(true)
            ->setFactory([CallbackFactory::class, 'createServiceWithClassFactory'])
            ->setArguments([$factory, new Reference('service_container'), $id]);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function getFactoryObjectServiceId(string $id): string
    {
        return $this->zendSmSfDiBridgeCreateId("{$id}.factory.service");
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
    protected function injectDelegators(string $id, array $delegators, ContainerBuilder $builder): void
    {
        if ($builder->hasAlias($id)) {
            /**
             * Delegators for aliases are ignored
             *
             * @see DelegatorTestTrait::testDelegatorsNamedForAliasDoNotApplyToInvokableServiceWithAlias
             */
            return;
        }

        if (! $builder->hasDefinition($id)) {
            /**
             * Delegators for [synthetic] services are ignored
             *
             * @see DelegatorTestTrait::testDelegatorsDoNotOperateOnServices
             */
            if ($builder->has($id)) {
                return;
            }

            throw new UnexpectedValueException(
                "Delegators for undefined services are not supported ({$id})"
            );
        }

        $definition = $builder->getDefinition($id);

        //Silently ignore delegators for synthetic definitions
        if ($definition->isSynthetic()) {
            return;
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
            ->setFactory([CallbackFactory::class, 'createFactoryCallback'])
            ->setArguments([new Reference('service_container'), $originId]);

        foreach ($delegators as $key => $delegator) {
            $delegatorFactoryCallbackId = $this->zendSmSfDiBridgeCreateId(
                "{$id}.delegator.{$key}.callback"
            );

            /**
             * support for invokable (object) and [(object), 'method'] as delegator
             *
             * - allow for dumping/caching using synthetic services
             */
            if (is_object($delegator) ||
                (is_array($delegator) && is_object($delegator[0]) && is_string($delegator[1]))
            ) {
                //register delegator (object) as service
                $delegatorObject = is_object($delegator) ? $delegator : $delegator[0];
                $delegatorObjectServiceId = $this->getDelegatorObjectServiceId($id, $key);

                if ($this->servicesAsSynthetic) {
                    $builder->register($delegatorObjectServiceId, get_class($delegatorObject))
                        ->setSynthetic(true)
                        ->setPublic(true);
                } else {
                    $builder->set($delegatorObjectServiceId, $delegatorObject);
                }

                if (is_object($delegator)) {
                    $builder->register($delegatorFactoryCallbackId, Closure::class)
                        ->setPublic(false)
                        ->setFactory([CallbackFactory::class, 'createDelegatorFactoryCallback'])
                        ->setArguments([
                            new Reference($delegatorObjectServiceId),
                            new Reference('service_container'),
                            $id,
                            new Reference($factoryCallbackId)
                        ]);
                } else {
                    $builder->register($delegatorFactoryCallbackId, Closure::class)
                        ->setPublic(false)
                        ->setFactory([CallbackFactory::class, 'createDelegatorFactoryCallbackFromObjectMethodCallable'])
                        ->setArguments([
                            new Reference($delegatorObjectServiceId),
                            $delegator[1],
                            new Reference('service_container'),
                            $id,
                            new Reference($factoryCallbackId)
                        ]);
                }
            } else {
                $builder
                    ->register($delegatorFactoryCallbackId, Closure::class)
                    ->setPublic(false)
                    ->setFactory([CallbackFactory::class, 'createDelegatorFactoryCallback'])
                    ->setArguments([
                        $delegator,
                        new Reference('service_container'),
                        $id,
                        new Reference($factoryCallbackId)
                    ]);
            }

            $factoryCallbackId = $delegatorFactoryCallbackId;
        }

        //Finally, register the last Closure as factory for the service
        //This action removes any previous alias registered for $id
        $builder
            ->register($id, $definition->getClass())
            ->setFactory([new Reference($factoryCallbackId), '__invoke'])
            ->setPublic($definition->isPublic());
    }

    /**
     * @param string $id
     * @param string|int $index
     *
     * @return string
     */
    protected function getDelegatorObjectServiceId(string $id, $index): string
    {
        return $this->zendSmSfDiBridgeCreateId("{$id}.delegator.{$index}.service");
    }

    /**
     * @param string $invokable
     * @param ContainerBuilder $builder
     * @param string|null $prefix
     *
     * @return void
     */
    private function registerWrapperForService(
        string $invokable,
        ContainerBuilder $builder,
        string $prefix = null
    ): void {
        $wrapperId = $prefix
            //@codeCoverageIgnoreStart
            ? $this->zendSmSfDiBridgeCreateId("{$invokable}.{$prefix}.wrapper")
            //@codeCoverageIgnoreEnd
            : $this->zendSmSfDiBridgeCreateId("{$invokable}.wrapper");

        $builder->register($wrapperId, $invokable)->setPublic(true);

        $wrapperFactoryId = "{$wrapperId}.factory";
        $builder->register($wrapperFactoryId, Closure::class)
            ->setFactory([CallbackFactory::class, 'createFactoryCallback'])
            ->setArguments([new Reference('service_container'), $wrapperId])
            ->setPublic(false);

        $builder->register($invokable, $invokable)
            ->setFactory([new Reference($wrapperFactoryId), '__invoke'])
            ->setPublic(true);
    }
}
