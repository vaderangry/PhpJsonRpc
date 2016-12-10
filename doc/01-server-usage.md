# Using server

 - [Basic concepts](#basic-consepts)
 - [Mapping](#Mapping)
 - [Interception](#Interception)

## Basic concepts

Process of request begin with class `Server\RequestParser`, when request is simple string and result is instance of
`Core\InvokeSpec`. `Core\InvokeSpec` is specification of invoke in
*JSON-RPC2* terms. This class contains one or few implementations of `Core\Invoke\AbstractInvoke` class and
presents invoke information (class `Core\Invoke\Invoke` or `Core\Invoke\Notification`). On parsing stage may occurred error which will
persist in `Core\Invoke\Error` instance.

On next stage instance of `Core\InvokeSpec` passed in `Server\Processor` which invokes php methods.

In conclusion `Server\Processor` produced instance of `Core\Result\ResultSpec` contains one or few implementations of

## Mapping

*Mapping* is mechanism which provide functions for mapping requested method on php-methods. Default
mapper perform mapping as is, that is `Repository.User.getOne` be mapped on `\Repository\User::getOne`.
You can define custom mapper by implement `Server\MapperInterface`. Then you should set him in `Server\Processor::setMapper`.

## Interception

*Interception* provide methods for interception requests and responses and modify their. Interception based on `Chain of responsibility`
pattern which allows perform sequential processing of data.

Available points of interception:

 - `Server\RequestParser::onPreParse`
 - `Server\Processor::onPreProcess`
 - `Server\ResponseBuilder::onPreBuild`
