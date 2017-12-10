<?php

namespace JSoumelidisTest\SymfonyDI\Config\Service;

class ServiceWithDependency
{
    protected $dependency;

    public function __construct($dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency()
    {
        return $this->dependency;
    }
}
