<?php

namespace PhpJsonRpc;

use PhpJsonRpc\Common\TypeAdapter\TypeAdapter;
use PhpJsonRpc\Server\Processor;
use PhpJsonRpc\Server\RequestParser;
use PhpJsonRpc\Server\RequestProvider;
use PhpJsonRpc\Server\RequestProviderInterface;
use PhpJsonRpc\Server\ResponseBuilder;
use PhpJsonRpc\Server\MapperInterface;

/**
 * Implementation of JSON-RPC2 server specification
 *
 * @link http://www.jsonrpc.org/specification 
 */
class Server
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var RequestProviderInterface
     */
    private $requestProvider;

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
     */
    public function __construct()
    {
        $this->processor = new Processor();

        $this->requestProvider = new RequestProvider();
        $this->requestParser   = new RequestParser();
        $this->responseBuilder = new ResponseBuilder();
    }

    /**
     * @return Processor
     */
    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    /**
     * @return RequestProviderInterface
     */
    public function getRequestProvider(): RequestProviderInterface
    {
        return $this->requestProvider;
    }

    /**
     * @param RequestProviderInterface $requestProvider
     */
    public function setRequestProvider(RequestProviderInterface $requestProvider)
    {
        $this->requestProvider = $requestProvider;
    }

    /**
     * @return RequestParser
     */
    public function getRequestParser(): RequestParser
    {
        return $this->requestParser;
    }

    /**
     * @return ResponseBuilder
     */
    public function getResponseBuilder(): ResponseBuilder
    {
        return $this->responseBuilder;
    }

    /**
     * @return TypeAdapter
     */
    public function getTypeAdapter(): TypeAdapter
    {
        return $this->processor->getInvoker()->getTypeAdapter();
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
     * Set mapper-object for mapping request-method on class and method
     *
     * @param MapperInterface $mapper
     * @return $this
     */
    public function setMapper(MapperInterface $mapper)
    {
        $this->processor->setMapper($mapper);
        return $this;
    }

    /**
     * Run server
     *
     * @return string
     */
    public function execute(): string
    {
        $calls  = $this->requestParser->parse($this->requestProvider->getPayload());
        $result = $this->processor->process($calls);

        return $this->responseBuilder->build($result);
    }
}
