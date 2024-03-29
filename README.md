# Chimera - bus/tactician

[![Total Downloads]](https://packagist.org/packages/chimera/bus-tactician)
[![Latest Stable Version]](https://packagist.org/packages/chimera/bus-tactician)
[![Unstable Version]](https://packagist.org/packages/chimera/bus-tactician)

[![Build Status]](https://github.com/chimeraphp/bus-tactician/actions?query=workflow%3A%22PHPUnit%20Tests%22+branch%3A1.0.x)
[![Code Coverage]](https://codecov.io/gh/chimeraphp/bus-tactician)

> The term Chimera (_/kɪˈmɪərə/_ or _/kaɪˈmɪərə/_) has come to describe any
mythical or fictional animal with parts taken from various animals, or to
describe anything composed of very disparate parts, or perceived as wildly
imaginative, implausible, or dazzling.

There are many many amazing libraries in the PHP community and with the creation
and adoption of the PSRs we don't necessarily need to rely on full stack
frameworks to create a complex and well designed software. Choosing which
components to use and plugging them together can sometimes be a little
challenging.

The goal of this set of packages is to make it easier to do that (without
compromising the quality), allowing you to focus on the behaviour of your
software.

This project provides an implementation for `chimera/foundation` that
uses [`league/tactician`](https://tactician.thephpleague.com) as service bus.

## Installation

Package is available on [Packagist], you can install it using [Composer].

```shell
composer require chimera/bus-tactician
```

## Usage

The only thing you need to do in order to plug tactician into chimera is to
create an instance of the command bus [as you usually do](https://tactician.thephpleague.com)
and pass it to the decorator:

```php
<?php

use Chimera\ServiceBus\Tactician\ServiceBus;
use League\Tactician\CommandBus;

$middlewareList = []; // list of middleware to be used to process commands
$commandBus     = new ServiceBus(new CommandBus($middlewareList));
```

Usually the write and read concerns have different needs, which means that the
list of middleware will definitely vary, so it's highly suggested that you create
two service buses: a query bus and a command bus:

```php
<?php

use Chimera\ServiceBus\Tactician\ServiceBus;
use League\Tactician\CommandBus;

$writeMiddleware = []; // list of middleware to be used to process commands
$commandBus      = new ServiceBus(new CommandBus($writeMiddleware));

$readMiddleware = []; // list of middleware to be used to process queries
$queryBus       = new ServiceBus(new CommandBus($readMiddleware));
```

### Domain to read model conversion

It's a good practice to completely isolate your domain model from your read model
(also known as response model). This is important to prevent UI components (e.g.:
request handlers - HTTP controllers - or CLI commands) from manipulating your
aggregate roots and entities.

We provide the `ReadModelConversionMiddleware` to handle such thing, and it should
be added to your query bus (since nothing is really returned from command buses):

```php
<?php

use Chimera\ServiceBus\ReadModelConverter\Callback;
use Chimera\ServiceBus\Tactician\ReadModelConversionMiddleware;
use Chimera\ServiceBus\Tactician\ServiceBus;
use League\Tactician\CommandBus;

// list of middleware to be used to process queries
$readMiddleware = [
    // many different middleware according to your needs
    new ReadModelConversionMiddleware(new Callback()), // you can use different strategies if needed
    // the handler locator middleware provided by tactician
];

$queryBus = new ServiceBus(new CommandBus($readMiddleware));
```

## License

MIT, see [LICENSE].

[Total Downloads]: https://img.shields.io/packagist/dt/chimera/bus-tactician.svg?style=flat-square
[Latest Stable Version]: https://img.shields.io/packagist/v/chimera/bus-tactician.svg?style=flat-square
[Unstable Version]: https://img.shields.io/packagist/vpre/chimera/bus-tactician.svg?style=flat-square
[Build Status]: https://img.shields.io/github/actions/workflow/status/chimeraphp/bus-tactician/phpunit.yml?branch=1.0.x&style=flat-square
[Code Coverage]: https://codecov.io/gh/chimeraphp/bus-tactician/branch/master/graph/badge.svg
[Packagist]: http://packagist.org/packages/chimera/bus-tactician
[Composer]: http://getcomposer.org
[LICENSE]: LICENSE
