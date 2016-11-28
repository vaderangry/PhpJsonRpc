# JsonRpc

JSON-RPC2 implementation for PHP7.

## Installation

Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

```console
$ composer require vaderangry/php-json-rpc
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.

## Usage

### Class binding

1. Create instance of **Server**.
2. Add class-handler to server.
3. Run server.

File **MyExampleHandler.php**:
```php
<?php

namespace Handler;

class MyExampleHandler
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

use Handler\MyExampleHandler;

$server = new Server($request);

$response = $server
    ->addHandler(new MyExampleHandler())
    ->execute();

echo $response;
```

RPC request for call method *MyExampleHandler::pow*:
```JSON
{
  "jsonrpc": "2.0",
  "method": "Handler.MyExampleHandler.pow",
  "params": {"base": 3, "exp": 7},
  "id": 1
}
```

### Custom routing

File *MyCustomerMapper.php*:
```php
<?php
class MyCustomMapper implements MapperInterface
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

use Handler\MyExampleHandler;

$server = new Server($request);

$response = $server
    ->addMapper(new MyCustomMapper())
    ->addHandler(new UserExample())
    ->execute();

echo $response;
```

## Tests

```Bash
$ ./vendor/bin/phpunit -c ./
```

## TODO

 - Check comments and improve docs
 - Add support user-defined classes (type-matching)
 - Add user-defined routes for class-handlers
 - Add JSON-RPC2 Client
