# PhpJsonRpc

[JSON-RPC2](http://www.jsonrpc.org/specification) implementation for PHP7.

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vaderangry/PhpJsonRpc/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vaderangry/PhpJsonRpc/?branch=master)
[![Build Status](https://travis-ci.org/vaderangry/PhpJsonRpc.svg?branch=master)](https://travis-ci.org/vaderangry/PhpJsonRpc)

## Features

 - JSON-RPC 2.0 full conformance​.
 - Fast start with default routing based on php namespaces.
 - Flexible custom routing for your requirements.
 - Fully unit tested.

## Installation

Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

```console
$ composer require vaderangry/php-json-rpc
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.

## Usage

1. Create instance of **Server**.
2. Add class-handler to server.
3. Run server.

### Examples

File **MyExampleHandler.php**:
```php
<?php

namespace Handler;

class MyHandler
{
    /**
     * @param float $base
     * @param int $exp
     * @return float
     */
    public function pow(float $base, int $exp): float
    {
        return pow($base, $exp);
    }
}
```

File **MyServer.php** (entry point for all requests):
```php
<?php

use Handler\MyHandler;

$server = new Server($request);

$response = $server
    ->addHandler(new MyHandler())
    ->execute();

echo $response;
```

RPC request for call method *MyHandler::pow*:
```JSON
{
  "jsonrpc": "2.0",
  "method": "Handler.MyHandler.pow",
  "params": {"base": 3, "exp": 7},
  "id": 1
}
```

### Custom routing

File *MyMapper.php*:
```php
<?php
class MyMapper implements MapperInterface
{
    public function getClassAndMethod(string $requestedMethod): array
    {
        // Keys of array presents requested method
        $map = [
            'User.Profile.create' => [UserExample::class, 'create'],
            'User.Profile.block'  => [UserExample::class, 'block']
        ];

        if (array_key_exists($requestedMethod, $map)) {
            return $map[$requestedMethod];
        }

        return ['', ''];
    }
}
```

File **MyServer.php**:
```php
<?php

use Handler\MyHandler;

$server = new Server($request);

$response = $server
    ->addMapper(new MyMapper())
    ->addHandler(new UserExample())
    ->execute();

echo $response;
```

## Tests

```Bash
$ ./vendor/bin/phpunit -c ./
```

## TODO

 - Add comments
 - Add support user-defined classes (type-matching)
 - Add JSON-RPC2 Client
