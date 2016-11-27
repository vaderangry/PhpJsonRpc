<?php

namespace PhpJsonRpc;

use PhpJsonRpc\Server\Processor;
use PhpJsonRpc\Server\RequestParser;
use PhpJsonRpc\Server\ResponseBuilder;
use PhpJsonRpc\Server\MapperInterface;

/**
 * Implementation of JSON-RPC2 specification
 *
 * @link http://www.jsonrpc.org/specification 
 */
class Server
{
    /**
     * @var string array
     */
    private $payload;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var RequestParser
     */
    private $requestParser;

    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * Server constructor.
     *
     * @param string $payload
     */
    public function __construct(string $payload = null)
    {
        $this->payload   = $payload ?? file_get_contents('php://input');
        $this->processor = new Processor();

        $this->requestParser   = new RequestParser();
        $this->responseBuilder = new ResponseBuilder();
    }

    /**
     * Add handler-object for handling request
     *
     * @param mixed $object
     * @return $this
     */
    public function addHandler($object)
    {
        $this->processor->addHandler($object);
        return $this;
    }

    /**
     * Add mapper-object for mapping request-method on class and method
     *
     * @param MapperInterface $mapper
     * @return $this
     */
    public function addMapper(MapperInterface $mapper)
    {
        $this->processor->addMapper($mapper);
        return $this;
    }

    /**
     * Run server
     *
     * @return string
     */
    public function execute(): string
    {
        $calls  = $this->requestParser->parse($this->payload);
        $result = $this->processor->process($calls);

        return $this->responseBuilder->build($result);
    }
}
