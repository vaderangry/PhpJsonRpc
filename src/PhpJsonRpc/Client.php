<?php

namespace PhpJsonRpc;

use PhpJsonRpc\Client\IdGenerator;
use PhpJsonRpc\Client\IdGeneratorInterface;
use PhpJsonRpc\Core\Result\ResultError;
use PhpJsonRpc\Error\InvalidResponseException;
use PhpJsonRpc\Client\HttpTransport;
use PhpJsonRpc\Client\TransportInterface;
use PhpJsonRpc\Client\RequestBuilder;
use PhpJsonRpc\Client\ResponseParser;
use PhpJsonRpc\Core\Call\AbstractCall;
use PhpJsonRpc\Core\Call\CallNotification;
use PhpJsonRpc\Core\Call\CallUnit;
use PhpJsonRpc\Core\CallSpecifier;
use PhpJsonRpc\Core\Result\ResultUnit;
use PhpJsonRpc\Core\ResultSpecifier;

class Client
{
    /**
     * @var AbstractCall[]
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
     * @var TransportInterface
     */
    private $engine;

    /**
     * @var ResponseParser
     */
    private $responseParser;

    /**
     * @var IdGeneratorInterface
     */
    private $generatorId;

    /**
     * Client constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->requestBuilder = new RequestBuilder();
        $this->engine         = new HttpTransport($url);
        $this->responseParser = new ResponseParser();
        $this->generatorId    = new IdGenerator();
    }

    /**
     * Set transport engine
     *
     * @param TransportInterface $engine
     */
    public function setTransport(TransportInterface $engine)
    {
        $this->engine = $engine;
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
     */
    public function call(string $method, array $parameters)
    {
        $this->units[] = new CallUnit($this->generatorId->get(), $method, $parameters);

        if ($this->isSingleRequest) {
            $resultSpecifier = $this->execute(new CallSpecifier($this->units, true));

            if (!$resultSpecifier->isSingleResult()) {
                throw new InvalidResponseException();
            }

            list($result) = $resultSpecifier->getResults();

            if ($result instanceof ResultUnit) {
                /** @var ResultUnit $result */
                return $result->getResult();
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
        $this->units[] = new CallNotification($method, $parameters);

        if ($this->isSingleRequest) {
            $this->execute(new CallSpecifier($this->units, true));
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
        return $this;
    }

    /**
     * Execute batch request
     *
     * @return array
     */
    public function batchExecute()
    {
        $results = $this->execute(new CallSpecifier($this->units, false))->getResults();

        // Make right order in sequence of results. It's required operation, because JSON-RPC2
        // specification define: "The Response objects being returned from a batch call MAY be returned
        // in any order within the Array. The Client SHOULD match contexts between the set of Request objects and the
        // resulting set of Response objects based on the id member within each Object."

        $callMap = [];
        foreach ($this->units as $index => $unit) {
            /** @var CallUnit $unit */
            $callMap[$unit->getRawId()] = $index;
        }

        if (count($results) !== count($this->units)) {
            throw new InvalidResponseException();
        }

        $resultSequence = [];
        foreach ($results as $result) {
            if ($result instanceof ResultUnit) {
                /** @var ResultUnit $result */
                $resultSequence[ $callMap[$result->getId()] ] = $result->getResult();
            } elseif ($result instanceof ResultError) {
                /** @var ResultError $result */
                $resultSequence[ $callMap[$result->getId()] ] = null;
            }
        }
        ksort($resultSequence);

        return $resultSequence;
    }

    /**
     * @param CallSpecifier $call
     *
     * @return ResultSpecifier
     */
    private function execute(CallSpecifier $call): ResultSpecifier
    {
        $request  = $this->requestBuilder->build($call);
        $response = $this->engine->request($request);

        return $this->responseParser->parse($response);
    }
}
