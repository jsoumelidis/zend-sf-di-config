<?php

namespace JSoumelidisTest\SymfonyDI\Config\Factory;

use JSoumelidisTest\SymfonyDI\Config\Service\ServiceWithDependency;
use Psr\Container\ContainerInterface;

class Delegator1
{
    public function __invoke(ContainerInterface $container, $requestedName, callable $factory)
    {
        $object = $factory();

        return new ServiceWithDependency($object);
    }
}
