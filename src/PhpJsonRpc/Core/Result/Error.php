<?php

namespace PhpJsonRpc\Core\Result;

use PhpJsonRpc\Error\JsonRpcException;

class Error extends AbstractResult
{
    /**
     * @var mixed
     */
    private $id;

    /**
     * @var JsonRpcException
     */
    private $baseException;

    /**
     * ErrorUnit constructor.
     *
     * @param mixed            $id
     * @param JsonRpcException $baseException
     */
    public function __construct($id, JsonRpcException $baseException)
    {
        $this->id            = $id;
        $this->baseException = $baseException;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return JsonRpcException
     */
    public function getBaseException()
    {
        return $this->baseException;
    }
}
