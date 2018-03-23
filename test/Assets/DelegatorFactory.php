<?php

namespace JSoumelidisTest\SymfonyDI\Config\Assets;

use Psr\Container\ContainerInterface;
use Zend\ContainerConfigTest\TestAsset\Service;

class DelegatorFactory
{
    public static function create(ContainerInterface $container, string $name, callable $callback)
    {
        /** @var Service $service */
        $service = $callback();

        $service->injected[] = $container;

        return $service;
    }
}
