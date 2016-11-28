<?php

namespace Vaderangry\PhpJsonRpc\Error;

class MethodNotFoundException extends JsonRpcException
{
    /**
     * @inheritdoc
     */
    public function getJsonRpcCode(): int
    {
        return self::METHOD_NOT_FOUND;
    }
}
