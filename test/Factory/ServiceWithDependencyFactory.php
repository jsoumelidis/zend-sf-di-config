<?php

namespace JSoumelidisTest\SymfonyDI\Config\Factory;

use JSoumelidisTest\SymfonyDI\Config\Service\ServiceWithDependency;
use Psr\Container\ContainerInterface;

class ServiceWithDependencyFactory
{
    protected $dependency;

    public function __construct($dependency = 'dependency')
    {
        $this->dependency = $dependency;
    }

    public static function create(ContainerInterface $container, $requestedName)
    {
        return (new static())->__invoke($container, $requestedName);
    }

    public function __invoke(ContainerInterface $container, $requestedName)
    {
        $dependency = $container->get($this->dependency);

        return new ServiceWithDependency($dependency);
    }
}
