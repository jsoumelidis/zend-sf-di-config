<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;
use Psr\Container\ContainerInterface;
use Zend\ContainerConfigTest\AbstractExpressiveContainerConfigTest;

class ContainerTest extends AbstractExpressiveContainerConfigTest
{
    protected function createContainer(array $config) : ContainerInterface
    {
        $factory = new ContainerFactory();

        $container = $factory(new Config(['dependencies' => $config]));

        //Everything should work with compiled container also
        $container->compile();

        return $container;
    }
}
