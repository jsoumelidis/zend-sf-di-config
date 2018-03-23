<?php

namespace JSoumelidis\SymfonyDI\Config\Exception;

use Psr\Container\ContainerExceptionInterface;

class ServiceNotFoundException extends \Exception implements ContainerExceptionInterface
{
    /**
     * @param string $message
     * @param \Throwable|null $previous
     *
     * @return self
     */
    public static function create(string $message, \Throwable $previous = null): self
    {
        return new static($message, 0, $previous);
    }
}
