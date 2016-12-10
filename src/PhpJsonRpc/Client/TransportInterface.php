<?php

namespace PhpJsonRpc\Client;

/**
 * Transport engine interface
 */
interface TransportInterface
{
    /**
     * Execute request 
     *
     * @param string $request
     * @return string
     */
    public function send(string $request): string;
}
