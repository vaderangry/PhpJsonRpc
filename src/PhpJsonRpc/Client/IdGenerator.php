<?php

namespace PhpJsonRpc\Client;

class IdGenerator implements IdGeneratorInterface
{
    public function get()
    {
        return mt_rand();
    }
}
