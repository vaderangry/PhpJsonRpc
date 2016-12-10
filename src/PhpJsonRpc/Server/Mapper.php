<?php

namespace PhpJsonRpc\Server;

class Mapper implements MapperInterface
{
    /**
     * @inheritdoc
     */
    public function getClassAndMethod(string $requestedMethod): array
    {
        $chunks = explode('.', $requestedMethod);
        $class  = array_slice($chunks, 0, -1);
        return [implode('\\', $class), end($chunks)];
    }
}
