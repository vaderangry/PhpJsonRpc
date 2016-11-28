<?php

namespace Vaderangry\PhpJsonRpc\Server;

class GeneralMapper implements MapperInterface
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
