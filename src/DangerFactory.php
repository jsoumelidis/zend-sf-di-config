<?php

namespace JSoumelidis\SymfonyDI\Config;

use Psr\Container\ContainerInterface;

class DangerFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     *
     * @return mixed
     *
     * @throws Exception\ServiceNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function create(ContainerInterface $container, $requestedName)
    {
        try {
            return $container->get($requestedName);
        } catch (\ReflectionException $e) {
            throw new Exception\ServiceNotFoundException($e->getMessage(), 0, $e);
        }
    }
}
