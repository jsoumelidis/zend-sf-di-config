<?php

namespace JSoumelidisTest\SymfonyDI\Config\Service;

class Wrapper
{
    protected $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }
}
