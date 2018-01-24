<?php

namespace PhpJsonRpc;

use PhpJsonRpc\Client\IdGenerator;
use PhpJsonRpc\Client\IdGeneratorInterface;
use PhpJsonRpc\Core\Result\Error;
use PhpJsonRpc\Error\InvalidResponseException;
use PhpJsonRpc\Client\HttpTransport;
use PhpJsonRpc\Client\AbstractTransport;
use PhpJsonRpc\Client\RequestBuilder;
use PhpJsonRpc\Client\ResponseParser;
use PhpJsonRpc\Core\Invoke\AbstractInvoke;
use PhpJsonRpc\Core\Invoke\Notification;
use PhpJsonRpc\Core\Invoke\Invoke;
use PhpJsonRpc\Core\InvokeSpec;
use PhpJsonRpc\Core\Result\Result;
use PhpJsonRpc\Core\ResultSpec;
use PhpJsonRpc\Error\JsonRpcException;

/**
 * Implementation of JSON-RPC2 client specification
 *
 * @link http://www.jsonrpc.org/specification
 */
class Client
{
    /**
     * Client will return null if server error happened
     */
    const ERRMODE_SILENT    = 0;

    /**
     * Client will throw exception if server error happened
     */
    const ERRMODE_EXCEPTION = 2;

    /**
     * @var AbstractInvoke[]
     */
    private $units = [];

    /**
     * @var bool
     */
    private $isSingleRequest = true;

    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * @var AbstractTransport
     */
    private $transport;

    /**
     * @var ResponseParser
     */
    private $responseParser;

    /**
     * @var IdGeneratorInterface
     */
    private $generatorId;

    /**
     * @var int
     */
    private $serverErrorMode;

    /**
     * Client constructor.
     *
     * @param string $url
     * @param int    $serverErrorMode
     */
    public function __construct(string $url, int $serverErrorMode = self::ERRMODE_SILENT)
    {
        $this->requestBuilder  = new RequestBuilder();
        $this->transport       = new HttpTransport($url);
        $this->responseParser  = new ResponseParser();
        $this->generatorId     = new IdGenerator();
        $this->serverErrorMode = $serverErrorMode;
    }

    /**
     * @return RequestBuilder
     */
    public function getRequestBuilder(): RequestBuilder
    {
        return $this->requestBuilder;
    }

    /**
     * @return AbstractTransport
     */
    public function getTransport(): AbstractTransport
    {
        return $this->transport;
    }

    /**
     * Set transport engine
     *
     * @param AbstractTransport $transport
     */
    public function setTransport(AbstractTransport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @return ResponseParser
     */
    public function getResponseParser()
    {
        return $this->responseParser;
    }

    /**
     * @param ResponseParser $responseParser
     */
    public function setResponseParser($responseParser)
    {
        $this->responseParser = $responseParser;
    }

    /**
     * @return IdGeneratorInterface
     */
    public function getGeneratorId(): IdGeneratorInterface
    {
        return $this->generatorId;
    }

    /**
     * @param IdGeneratorInterface $idGenerator
     */
    public function setIdGenerator(IdGeneratorInterface $idGenerator)
    {
        $this->generatorId = $idGenerator;
    }

    /**
     * Make request
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return $this|mixed
     * @throws JsonRpcException
     */
    public function call(string $method, array $parameters)
    {
        if ($this->isSingleRequest) {
            $this->units = [];
        }

        $this->units[] = new Invoke($this->generatorId->get(), $method, $parameters);

        if ($this->isSingleRequest) {
            $resultSpecifier = $this->execute(new InvokeSpec($this->units, true));

            if (!$resultSpecifier->isSingleResult()) {
                throw new InvalidResponseException();
            }

            list($result) = $resultSpecifier->getResults();

            if ($result instanceof Result) {
                /** @var Result $result */
                return $result->getResult();
            } elseif ($result instanceof Error) {
                /** @var Error $result */
                if ($this->serverErrorMode === self::ERRMODE_EXCEPTION) {
                    throw $result->getBaseException();
                }
            }

            return null;
        }

        return $this;
    }

    /**
     * Make notification request
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return $this|null
     */
    public function notification(string $method, array $parameters)
    {
        if ($this->isSingleRequest) {
            $this->units = [];
        }

        $this->units[] = new Notification($method, $parameters);

        if ($this->isSingleRequest) {
            $this->execute(new InvokeSpec($this->units, true));
            return null;
        }

        return $this;
    }

    /**
     * Start batch request
     *
     * @return $this
     */
    public function batch()
    {
        $this->isSingleRequest = false;
        $this->units = [];
        return $this;
    }

    /**
     * Execute batch request
     *
     * @return array
     */
    public function batchExecute()
    {
        $results = $this->execute(new InvokeSpec($this->units, false))->getResults();

        // Make right order in sequence of results. It's required operation, because JSON-RPC2
        // specification define: "The Response objects being returned from a batch call MAY be returned
        // in any order within the Array. The Client SHOULD match contexts between the set of Request objects and the
        // resulting set of Response objects based on the id member within each Object."

        $callMap = [];
        foreach ($this->units as $index => $unit) {
            /** @var Invoke $unit */
            $callMap[$unit->getRawId()] = $index;
        }

        if (count($results) !== count($this->units)) {
            throw new InvalidResponseException();
        }

        $resultSequence = [];
        foreach ($results as $result) {
            if ($result instanceof Result) {
                /** @var Result $result */
                $resultSequence[ $callMap[$result->getId()] ] = $result->getResult();
            } elseif ($result instanceof Error) {
                /** @var Error $result */
                $resultSequence[ $callMap[$result->getId()] ] = $this->serverErrorMode === self::ERRMODE_EXCEPTION ? $result->getBaseException() : null;
            }
        }
        ksort($resultSequence);
        $this->units = [];

        return $resultSequence;
    }

    /**
     * @param InvokeSpec $call
     *
     * @return ResultSpec
     */
    private function execute(InvokeSpec $call): ResultSpec
    {
        $request  = $this->requestBuilder->build($call);
        $response = $this->transport->request($request);

        return $this->responseParser->parse($response);
    }
}
