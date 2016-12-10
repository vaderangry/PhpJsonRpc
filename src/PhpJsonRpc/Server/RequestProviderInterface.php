<?php

namespace PhpJsonRpc\Server;

interface RequestProviderInterface
{
    /**
     * Get payload of current request
     *
     * @return string
     */
    public function getPayload(): string;
}
