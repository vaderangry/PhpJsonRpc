<?php

namespace PhpJsonRpc\Core\Call;

use PhpJsonRpc\Error\JsonRpcException;

class CallError extends AbstractCall
{
    /**
     * @var JsonRpcException
     */
    private $baseException;

    /**
     * ErrorUnit constructor.
     *
     * @param JsonRpcException $baseException
     */
    public function __construct(JsonRpcException $baseException)
    {
        $this->baseException = $baseException;
    }

    /**
     * @return JsonRpcException
     */
    public function getBaseException()
    {
        return $this->baseException;
    }
}
