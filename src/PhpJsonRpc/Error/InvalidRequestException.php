<?php

namespace Vaderangry\PhpJsonRpc\Error;

class InvalidRequestException extends JsonRpcException
{
    /**
     * @inheritdoc
     */
    public function getJsonRpcCode(): int
    {
        return self::INVALID_REQUEST;
    }
}
