# zend-sf-di-config

[![Build Status](https://secure.travis-ci.org/jsoumelidis/zend-sf-di-config.svg?branch=master)](https://secure.travis-ci.org/jsoumelidis/zend-sf-di-config)
[![Coverage Status](https://coveralls.io/repos/github/zendframework/zend-auradi-config/badge.svg?branch=master)](https://coveralls.io/github/zendframework/zend-auradi-config?branch=master)

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
- `factories`: an associative array that maps a service name to a factory class
  name, or any callable. Factory classes must be instantiable without arguments,
  and callable once instantiated (i.e., implement the `__invoke()` method).
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