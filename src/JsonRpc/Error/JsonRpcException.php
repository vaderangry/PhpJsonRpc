<?php

namespace JsonRpc\Error;

abstract class JsonRpcException extends \Exception
{
    const PARSE_ERROR      = -32700;
    const INVALID_REQUEST  = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS   = -32602;
    const INTERNAL_ERROR   = -32603;
    const SERVER_ERROR     = -32000;

    /**
     * @return int
     */
    abstract public function getJsonRpcCode(): int;

    /**
     * @return array
     */
    final public function getJsonRpcData()
    {
        return [
            'code'    => $this->getCode(),
            'message' => $this->getMessage()
        ];
    }
}
