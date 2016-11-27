<?php

namespace JsonRpc\Server;

interface MapperInterface
{
    /**
     * Get tuple of class and method
     *
     * @param string $requestedMethod
     * @return array Tuple [class, method] 
     */
    public function getClassAndMethod(string $requestedMethod): array;
}
