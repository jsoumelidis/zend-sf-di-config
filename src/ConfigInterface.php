<?php

namespace JSoumelidis\SymfonyDI\Config;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ConfigInterface
{
    public function configureContainerBuilder(ContainerBuilder $builder);
}
