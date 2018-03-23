<?php

namespace JSoumelidisTest\SymfonyDI\Config;

use Generator;
use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;
use Psr\Container\ContainerInterface;
use Zend\ContainerConfigTest\AliasTestTrait;
use Zend\ContainerConfigTest\DelegatorTestTrait;
use Zend\ContainerConfigTest\FactoryTestTrait;
use Zend\ContainerConfigTest\InvokableTestTrait;
use Zend\ContainerConfigTest\ServiceTestTrait;

class ContainerTest extends \Zend\ContainerConfigTest\ContainerTest
{
    use AliasTestTrait;
    use DelegatorTestTrait {
        factoriesForDelegators as protected __factoriesForDelegators;
    }
    use FactoryTestTrait {
        factoryWithName as protected __factoryWithName;
        factory as protected __factory;
    }
    use InvokableTestTrait;
    use ServiceTestTrait;

    protected function createContainer(array $config) : ContainerInterface
    {
        $factory = new ContainerFactory();
//$this->testFactoryIsProvidedContainerAndServiceNameAsArguments()
        return $factory(new Config(['dependencies' => $config]));
    }

    final public function factoryWithName() : Generator
    {
        $key = $this->__factoryWithName()->key();
        $val = $this->__factoryWithName()->current();

        if (in_array($key, ['invokable-instance', 'closure'])) {
            //does not support objects/closures as factory, go to next
            return $this->factoryWithName();
        }

        yield $key => $val;
    }

    final public function factory() : Generator
    {
        $key = $this->__factory()->key();
        $val = $this->__factory()->current();

        if (in_array($key, ['invokable-instance', 'closure'])) {
            //does not support objects/closures as factory, go to next
            return $this->factory();
        }

        yield $key => $val;
    }

    final public function factoriesForDelegators(): Generator
    {
        $key = $this->__factory()->key();
        $val = $this->__factory()->current();

        if (in_array($key, ['invokable-instance', 'closure'])) {
            //does not support objects/closures as delegators, go to next
            return $this->factory();
        }

        yield $key => $val;
    }
}
