<?php

namespace PhpJsonRpc\Error;

class ServerErrorException extends JsonRpcException
{
    /**
     * @inheritdoc
     */
    public function getJsonRpcCode(): int
    {
        return self::SERVER_ERROR;
    }
}
