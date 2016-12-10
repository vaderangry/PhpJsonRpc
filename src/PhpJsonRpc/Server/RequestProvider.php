<?php

namespace PhpJsonRpc\Server;

class RequestProvider implements RequestProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getPayload(): string
    {
        return file_get_contents('php://input');
    }
}
