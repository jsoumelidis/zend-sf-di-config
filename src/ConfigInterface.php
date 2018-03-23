<?php

namespace JSoumelidis\SymfonyDI\Config;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ConfigInterface
{
    /**
     * @param ContainerBuilder $builder
     */
    public function configureContainerBuilder(ContainerBuilder $builder);
}
