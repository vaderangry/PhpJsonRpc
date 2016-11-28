<?php

namespace Vaderangry\PhpJsonRpc\Error;

class InvalidParamsException extends JsonRpcException
{
    /**
     * @inheritdoc
     */
    public function getJsonRpcCode(): int
    {
        return self::INVALID_PARAMS;
    }
}
