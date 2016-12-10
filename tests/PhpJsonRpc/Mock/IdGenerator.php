<?php

namespace PhpJsonRpc\Tests\Mock;

use PhpJsonRpc\Client\IdGeneratorInterface;

class IdGenerator implements IdGeneratorInterface
{
    private $current = 1;

    public function get()
    {
        return $this->current++;
    }
}
