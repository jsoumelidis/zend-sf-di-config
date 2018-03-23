<?php

namespace JSoumelidisTest\SymfonyDI\Config\Assets;

use Psr\Container\ContainerInterface;
use Zend\ContainerConfigTest\TestAsset\Service;

class DelegatorFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     *
     * @return Service
     */
    public static function create(ContainerInterface $container, string $name, callable $callback): Service
    {
        /** @var Service $service */
        $service = $callback();

        $service->injected[] = $container;

        return $service;
    }
}
