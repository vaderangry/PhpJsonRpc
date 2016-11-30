# PhpJsonRpc

[JSON-RPC2](http://www.jsonrpc.org/specification) server and client implementation for PHP7.

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vaderangry/PhpJsonRpc/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vaderangry/PhpJsonRpc/?branch=master)
[![Build Status](https://travis-ci.org/vaderangry/PhpJsonRpc.svg?branch=master)](https://travis-ci.org/vaderangry/PhpJsonRpc)

## Features

 - JSON-RPC 2.0 full conformance (batch requests, notification, positional and named arguments, etc)​.
 - Fast start with default routing based on php namespaces.
 - Flexible custom routing for your requirements.
 - Fully unit tested.

## Installation

Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

```console
$ composer require vaderangry/php-json-rpc
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.

## Server
### Basic usage

File **Math.php** contains class for which provide JSON-RPC2 API:
```php
<?php

namespace Util;

class Math
{
    public function pow(float $base, int $exp): float
    {
        return pow($base, $exp);
    }
}
```

File **Server.php** - entry point for all requests:
```php
<?php

use PhpJsonRpc\Server;
use Util\Math;

$server = new Server($request);

$response = $server
    ->addHandler(new Math())
    ->execute();

echo $response;
```

Method `Util\Math::pow` by default will mapped to method `Util.Math.pow` in JSON-RPC2 terms. Request example:
```json
{
  "jsonrpc": "2.0", 
  "method": "Util.Math.pow", 
  "params": {"base": 2, "exp": 3}, 
  "id": 1
}
```

### Custom method mapping

File *Mapper.php*:
```php
<?php

use JsonRpc\Server\MapperInterface;
use Util\Math;

class Mapper implements MapperInterface
{
    public function getClassAndMethod(string $requestedMethod): array
    {
        // Keys of array presents requested method
        $map = [
            'pow' => [Math::class, 'pow'],
        ];

        if (array_key_exists($requestedMethod, $map)) {
            return $map[$requestedMethod];
        }

        return ['', ''];
    }
}
```

File **Server.php**:
```php
<?php

use PhphJsonRpc\Server;
use Util\Math;

$server = new Server($request);

$response = $server
    ->addMapper(new Mapper())
    ->addHandler(new Math())
    ->execute();

echo $response;
```

Now `Util\Math::pow` will be mapped to `pow`. Request example:
```json
{
  "jsonrpc": "2.0", 
  "method": "pow", 
  "params": {"base": 2, "exp": 3}, 
  "id": 1
}
```

## Client

### Basic usage

Single request:
```php
<?php

use PhphJsonRpc\Client;

$client = new Client('http://localhost');
$result = $client->call('Math.pow', [2, 3]); // $result = 8
```

Batch request:
```php
<?php

use PhphJsonRpc\Client;

$client = new Client('http://localhost')

$result = $client->batch()
    ->call('Util.Math.pow', [2, 1])
    ->call('Util.Math.pow', [2, 2])
    ->call('Util.Math.pow', [2, 3])
    ->batchExecute();
// $result = [2, 4, 8]
```
All unit of result stored at the same position of call. Server error present `null` object.

## Tests

```Bash
$ ./vendor/bin/phpunit -c ./
```

## TODO
 - Improve docs (custom transport, id generator, core concepts, configuration)
 - Add support user-defined classes (type-matching)
