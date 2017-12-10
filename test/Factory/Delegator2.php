<?php

namespace JSoumelidisTest\SymfonyDI\Config\Factory;

use JSoumelidisTest\SymfonyDI\Config\Service\Wrapper;
use Psr\Container\ContainerInterface;

class Delegator2
{
    public static function create(ContainerInterface $container, $requestedName, callable $factory)
    {
        $object = $factory();

        return new Wrapper($object);
    }
}
