# Using server

 - [Basic concepts](#basic-consepts)
 - [Method mapping](#method-mapping)
 - [Interception](#interception)
 - [Type casting](#type-casting)

## Basic concepts

![Server](Server.png)

Request processing begin with class `RequestParser`, where the request is a simple string and result is a instance of
`InvokeSpec` class. `InvokeSpec` is specification of invoke in
*JSON-RPC2* terms. This class contains one or few implementations of `AbstractInvoke` class and
presents invoke information (class `Invoke\Invoke` or `Invoke\Notification`). On parsing stage may occurred error which will
persist in `Invoke\Error` instance.

On next stage instance of `Core\InvokeSpec` pass in `Processor` which invokes php methods. `Processor` produced instance of `ResultSpec`
contains one or few implementations of `AbstractResult`.

Finally, the instance of `ResultSpec` passed to the `ResponseBuilder` that generates a response string and returns the result.

## Method mapping

*Mapping* is mechanism which provide functions for mapping requested method on php-methods. Default
mapper perform mapping as is, that is `Repository.User.getOne` be mapped on `\Repository\User::getOne`.
You can define custom mapper by implement `Server\MapperInterface`. Then you should set him in `Server\Processor::setMapper`.

The example of simple configuration:

```php
<?php

use PhpJsonRpc\Server\MapperInterface;

class SimpleMapper implements MapperInterface
{
    public function getClassAndMethod(string $requestedMethod): array
    {
        // Keys of array presents requested method
        $map = [
            'Order.getOne'  => [OrderRepository::class, 'getOne'],
            'Order.getList' => [OrderRepository::class, 'getList'],
        ];

        if (array_key_exists($requestedMethod, $map)) {
            return $map[$requestedMethod];
        }

        return ['', ''];
    }
}

/** @var PhpJsonRpc\Server $server */
$server->setMapper(new SimpleMapper());
$server->execute();

// ...
```

## Interception

*Interception* provide methods for interception requests and responses and modify their. Interception based on `Chain of responsibility`
pattern which allows perform sequential processing of data. Each new interceptor returns a reference to itself allowing you
to install multiple interceptors at once.

Available points of interception:

 - `RequestParser::onPreParse` - called before parsing the request.
 - `Processor::onPreProcess` - called before handle the call.
 - `ResponseBuilder::onPreBuild` - called before build the response.

Example of `RequestParser::onPreParse` with multiple interceptors:

```php
<?php

use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Server\RequestParser\ParserContainer;

/** @var PhpJsonRpc\Server $server */
$server->getRequestParser()->onPreParse()
    ->add(Interceptor::createWith(function (ParserContainer $container) {
        $parser  = $container->getParser();
        $request = $container->getValue();

        // Do something...

        return new ParserContainer($parser, $request);
    }))
    // We can continue processing the request to the next interceptor
    ->add(Interceptor::createWith(function (ParserContainer $container) {
        $parser  = $container->getParser();
        $request = $container->getValue();

        // Do something...

        return new ParserContainer($parser, $request);
    }));

$server->execute();
```

Example of `Processor::onPreProcess`:

```php
<?php

use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Server\Processor\ProcessorContainer;

/** @var PhpJsonRpc\Server $server */
$server->getProcessor()->onPreProcess()
    ->add(Interceptor::createWith(function (ProcessorContainer $container) {
        $invoke = $container->getInvoke();

        // Do something...

        return new ProcessorContainer($container->getProcessor(), $invoke);
    }));

$server->execute();

```

Example of `ResponseBuilder::onPreBuild`:

```php
<?php

use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Server\ResponseBuilder\BuilderContainer;

/** @var PhpJsonRpc\Server $server */
$server->getResponseBuilder()->onPreBuild()
    ->add(Interceptor::createWith(function (BuilderContainer $container) {
        $builder = $container->getBuilder();
        $value   = $container->getValue();

        // Do something...

        return new BuilderContainer($builder, $value);
    }));

$server->execute();

```

## Type casting

The type casting mechanism is implemented using the class `TypeAdapter` allows you to use in your code into a high-level
types (i.e., your classes) along with scalar types. All you need is to register the rules of the type cast in `TypeAdapter`.

Example:

```php
<?php

use PhpJsonRpc\Common\TypeAdapter\Rule;

class User
{
    /** @var int */
    public $id;

    /** @var string */
    public $email;

    /** @var string */
    public $name;
}

class UserRepository
{
    public function save(User $user)
    {
        // Do something...
    }
}

/** @var PhpJsonRpc\Server $server */
$server->getTypeAdapter()
    ->register(
        Rule::create(User::class)
            ->assign('id', 'id')        // You can define custom name for each property of object
            ->assign('email', 'email')
            ->assign('name', 'name')
        // Or simple use Rule::createDefault(User::class) for use property as key in map
    );

$server->addHandler(new UserRepository())->execute();
```

Now we can call the method `UserRepository.save` by passing argument the array with fields `id`, `email` and `name`.
This will create an object of registered type `User` before calling method `UserRepository.save`. If the return value matches with the registered types will
be produced by the inverse transform (object to array).
