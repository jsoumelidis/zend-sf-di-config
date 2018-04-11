# zend-sf-di-config

[![Build Status](https://secure.travis-ci.org/jsoumelidis/zend-sf-di-config.svg?branch=master)](https://secure.travis-ci.org/jsoumelidis/zend-sf-di-config)
[![Coverage Status](https://coveralls.io/repos/github/jsoumelidis/zend-sf-di-config/badge.svg?branch=master)](https://coveralls.io/github/jsoumelidis/zend-sf-di-config?branch=master)

This library provides utilities to configure
a [PSR-11](http://www.php-fig.org/psr/psr-11/) compatible
[Symfony DI Container](https://github.com/symfony/dependency-injection)
using zend-servicemanager configuration.

## Installation

Run the following to install this library:

```bash
$ composer require jsoumelidis/zend-sf-di-config
```

## Configuration

To get a configured Symfony DI container, do the following:

```php
<?php
use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;

$factory = new ContainerFactory();

$container = $factory(
    new Config([
        'dependencies' => [
            'services'   => [],
            'invokables' => [],
            'factories'  => [],
            'aliases'    => [],
            'delegators' => [],
        ],
        // ... other configuration
    ])
);
```

The `dependencies` sub associative array can contain the following keys:

- `services`: an associative array that maps a key to a specific service instance.
- `invokables`: an associative array that map a key to a constructor-less
  service; i.e., for services that do not require arguments to the constructor.
  The key and service name may be the same; if they are not, the name is treated
  as an alias.
- `factories`: an associative array that maps a service name to a factory class name, 
  or any callable. Factory classes must be instantiable without arguments, and callable
  once instantiated (i.e., implement the __invoke() method).
- `aliases`: an associative array that maps an alias to a service name (or
  another alias).
- `delegators`: an associative array that maps service names to lists of
  delegator factory keys, see the
  [Expressive delegators documentation](https://docs.zendframework.com/zend-servicemanager/delegators/)
  for more details.

> Please note, that the whole configuration is available in the `$container`
> on `config` key:
>
> ```php
> $config = $container->get('config');
> ```

## Using with Expressive

Replace the contents of `config/container.php` with the following:

```php
<?php

use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;

$config  = require __DIR__ . '/config.php';
$factory = new ContainerFactory();

return $factory(new Config($config));
```

## Pre-configuring the ContainerBuilder

One can pass an already instantiated ContainerBuilder as 2nd argument
to ContainerFactory

```php
<?php

use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;

$config  = require __DIR__ . '/config.php';

$containerBuilder = new \Symfony\Component\DependencyInjection\ContainerBuilder();

//...Some work here...

$factory = new ContainerFactory();
$factory(new Config($config), $containerBuilder);

//Maybe you'll wish to compile the container and dump (cache) it
$containerBuilder->compile();
// ... other code

return $containerBuilder;
```

## Dumping/Caching the Container

If you are planning to use Symfony DI Container's dumping/caching functionality you
should be aware that Closures, callable objects or callables of type [(object), 'method']
as factories and/or delegators are not compatible, while php named functions and static method
callables are perfectly fine.

Services defined in `services` require to be registered as synthetic and re-set once the cached
container is booted like the following example:
```php
<?php
use JSoumelidis\SymfonyDI\Config\Config;
use JSoumelidis\SymfonyDI\Config\ContainerFactory;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

$factory = new ContainerFactory();

$config = require __DIR__ . '/config.php';
$cachedContainerFile = __DIR__ . '/../var/cache/container.php';

if (file_exists($cachedContainerFile)) {
    //load cached container
    require_once($cachedContainerFile);
    
    $container = new ProjectServiceContainer();
    
    //re-set synthetic services
    foreach ($config['dependencies']['services'] as $id => $service) {
        $container->set($id, $service);
    }
    
    //Config must be set again as a service separately
    $container->set('config', new ArrayObject($config));
    
    return $container;
}

$container = $factory(new Config($config, true)); //set 2nd argument to true while
                                                  //instantiating Config to register
                                                  //services as synthetic
$container->compile();
file_put_contents($cachedContainerFile, (new PhpDumper($container))->dump());

return $container;
```