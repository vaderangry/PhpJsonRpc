<?php

namespace PhpJsonRpc\Client;

use PhpJsonRpc\Client\Transport\TransportContainer;
use PhpJsonRpc\Common\Interceptor\Interceptor;

abstract class AbstractTransport implements TransportInterface
{
    /**
     * @var Interceptor
     */
    protected $preRequest;

    /**
     * AbstractTransport constructor.
     */
    public function __construct()
    {
        $this->preRequest  = Interceptor::createBase();
    }

    /**
     * @return Interceptor
     */
    public function onPreRequest(): Interceptor
    {
        return $this->preRequest;
    }

    /**
     * Execute request
     *
     * @param string $request
     *
     * @return string
     */
    public function request(string $request): string
    {
        $request  = $this->preRequest($request);
        return $this->send($request);
    }

    abstract public function send(string $request): string;

    /**
     * @param string $request
     *
     * @return string
     */
    private function preRequest(string $request): string
    {
        $result = $this->preRequest->handle(new TransportContainer($this, $request));

        if ($result instanceof TransportContainer) {
            return $result->getRequest();
        }

        throw new \RuntimeException();
    }
}
