# JsonRpc

JSON-RPC2 implementation for PHP7.

## Install

@todo After adding packagist.

## Usage

### Class binding

1. Create or take your exists class-handler.
2. Create instance of **Server**.
3. Add class-handler to server.
4. Run server.

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

File **MyServer.php** (your Controller-like end-point):
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

## TODO

 - Add test on custom mappers
 - Check comments and improve docs
 - Add support user-defined classes (type-matching)
 - Add user-defined routes for class-handlers
 - Add JSON-RPC2 Client
