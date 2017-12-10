<?php

namespace JSoumelidisTest\SymfonyDI\Config\Factory;

use JSoumelidisTest\SymfonyDI\Config\Service\InvokableService;
use JSoumelidisTest\SymfonyDI\Config\Service\ServiceWithDependency;
use JSoumelidisTest\SymfonyDI\Config\Service\Wrapper;
use Psr\Container\ContainerInterface;

if (! function_exists(__NAMESPACE__ . '\phpFunctionAsFactoryWithDependency')) {
    function phpFunctionAsFactoryWithDependency(ContainerInterface $container, $requestedName)
    {
        $dependency = $container->get('dependency');
        return new ServiceWithDependency($dependency);
    }
}

if (! function_exists(__NAMESPACE__ . '\phpFunctionAsFactory')) {
    function phpFunctionAsFactory(ContainerInterface $container, $requestedName)
    {
        return new InvokableService();
    }
}

if (! function_exists(__NAMESPACE__ . '\phpFunctionAsDelegatorFactory')) {
    function phpFunctionAsDelegatorFactory(ContainerInterface $container, $requestedName, callable $factory)
    {
        $service = $factory();
        return new Wrapper($service);
    }
}
