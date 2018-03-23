<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use JSoumelidis\SymfonyDI\Config\ConfigInterface;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContainerFactoryTest extends TestCase
{
    /**
     * @var ContainerFactory
     */
    private $factory;

    protected function setUp()
    {
        parent::setUp();

        $this->factory = new ContainerFactory();
    }

    public function testFactoryCreatesPsr11Container(): void
    {
        $factory = $this->factory;

        $config = $this->prophesize(ConfigInterface::class);
        $container = $factory($config->reveal());

        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testContainerIsConfigured(): void
    {
        $factory = $this->factory;

        $config = $this->prophesize(ConfigInterface::class);
        $config
            ->configureContainerBuilder(Argument::type(ContainerBuilder::class))
            ->shouldBeCalledTimes(1);

        $factory($config->reveal());
    }
}
