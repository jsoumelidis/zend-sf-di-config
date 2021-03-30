<?php

namespace JSoumelidis\SymfonyDI\Config\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Throwable;

class ServiceNotFoundException extends Exception implements ContainerExceptionInterface
{
    public static function create(string $message, ?Throwable $previous = null): self
    {
        return new static($message, 0, $previous);
    }
}
