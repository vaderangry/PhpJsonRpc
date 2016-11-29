<?php

namespace PhpJsonRpc\Client;

interface IdGeneratorInterface
{
    /**
     * Get unique ID
     *
     * @return string|int
     */
    public function get();
}
