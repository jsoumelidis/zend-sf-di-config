<?php

namespace JSoumelidisTest\SymfonyDI\Config\Factory;

use JSoumelidisTest\SymfonyDI\Config\Service\InvokableService;
use Psr\Container\ContainerInterface;

class InvokableServiceFactory
{
    public static function create(ContainerInterface $container, $requestedName)
    {
        return (new static())->__invoke($container, $requestedName);
    }

    public function __invoke(ContainerInterface $container, $requestedName)
    {
        return new InvokableService();
    }
}
