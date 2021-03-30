<?php

namespace JSoumelidis\SymfonyDI\Config;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContainerFactory
{
    /**
     * @return ContainerBuilder
     */
    public function __invoke(ConfigInterface $config, ?ContainerBuilder $builder = null): ContainerInterface
    {
        if (null === $builder) {
            $builder = new ContainerBuilder();
        }

        $config->configureContainerBuilder($builder);

        return $builder;
    }
}
