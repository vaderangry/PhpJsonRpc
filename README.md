# PhpJsonRpc

Flexible [JSON-RPC2](http://www.jsonrpc.org/specification) server/client implementation for PHP7.

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vaderangry/PhpJsonRpc/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vaderangry/PhpJsonRpc/?branch=master)
[![Build Status](https://travis-ci.org/vaderangry/PhpJsonRpc.svg?branch=master)](https://travis-ci.org/vaderangry/PhpJsonRpc)

## Features

 - JSON-RPC 2.0 full conformance (batch requests, notification, positional and named arguments, etc)​.
 - Quick-start with default routing based on php namespaces.
 - Flexible custom routing for your requirements.
 - The mechanism of intercepting requests and responses through handlers.
 - Automatic casting types in requests and responses.
 - Fully unit tested.

## Installation

Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

```console
$ composer require vaderangry/php-json-rpc
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.

## Documentation

 - [Server usage](doc/01-server-usage.md)
 - [Client usage](doc/02-client-usage.md)

## Basic usage

### Server

The example of quick-start:
```php
<?php

use PhpJsonRpc\Server;

// Class for which provide JSON-RPC2 API:
class Math
{
    public function pow(float $base, int $exp): float
    {
        return pow($base, $exp);
    }
}

$server = new Server();

$response = $server
    ->addHandler(new Math())
    ->execute();

echo $response;
```

Method `Math::pow` by default will mapped to method `Math.pow` in JSON-RPC2 terms. Request example:
```json
{
  "jsonrpc": "2.0", 
  "method": "Math.pow", 
  "params": {"base": 2, "exp": 3}, 
  "id": 1
}
```

The example of custom method mapping:

```php
<?php

use PhpJsonRpc\Server;
use PhpJsonRpc\Server\MapperInterface;

// Define custom mapper
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

$server = new Server();

// Register new mapper
$server->setMapper(new Mapper());

// Register handler and run server
$response = $server->addHandler(new Math())->execute();

echo $response;
```

Now `Math::pow` will be mapped to `pow`. Request example:
```json
{
  "jsonrpc": "2.0", 
  "method": "pow", 
  "params": {"base": 2, "exp": 3}, 
  "id": 1
}
```

### Client

Single request:
```php
<?php

use PhpJsonRpc\Client;

$client = new Client('http://localhost');
$result = $client->call('Math.pow', [2, 3]); // $result = 8
```

Batch request:
```php
<?php

use PhpJsonRpc\Client;

$client = new Client('http://localhost');

$result = $client->batch()
    ->call('Util.Math.pow', [2, 1])
    ->call('Util.Math.pow', [2, 2])
    ->call('Util.Math.pow', [2, 3])
    ->batchExecute();
// $result = [2, 4, 8]
```
All unit of result stored at the same position of call. Server error present `null` object.

The example with personal custom headers in request:
```php
<?php

use PhpJsonRpc\Client;
use PhpJsonRpc\Common\Interceptor\Container;
use PhpJsonRpc\Common\Interceptor\Interceptor;

$client = new Client('http://localhost');

$client->getTransport()->onPreRequest()
   ->add(Interceptor::createWith(function (Container $container) {
        // Get transport from container
        $transport = $container->first();

        // Add required headers
        $transport->addHeaders([
            "Origin: " . $_SERVER['HTTP_HOST'],
        ]);

        // Now we MUST return container for next chain
        return new Container($transport, $container->last());
    }));
    
$result = $client->call('Math.pow', [2, 3]); // $result = 8
```

## Tests

```Bash
$ ./vendor/bin/phpunit -c ./
```
