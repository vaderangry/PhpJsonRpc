<?php

namespace PhpJsonRpc\Core\Result;

use PhpJsonRpc\Error\JsonRpcException;

class ResultError extends AbstractResult
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
