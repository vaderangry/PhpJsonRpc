<?php

namespace PhpJsonRpc\Tests\Mock;

use PhpJsonRpc\Server\RequestProviderInterface;

class RequestProvider implements RequestProviderInterface
{
    private $payload;

    /**
     * RequestProvider constructor.
     *
     * @param $payload
     */
    public function __construct(string $payload)
    {
        $this->payload = $payload;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}
