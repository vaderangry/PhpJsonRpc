<?php

namespace JsonRpc\Server;

interface MapperInterface
{
    /**
     * Get class name from request method
     *
     * @param string $requestedMethod
     * @return string
     */
    public function getHandlerClass(string $requestedMethod): string;

    /**
     * Get method name from request method 
     *
     * @param string $requestedMethod
     * @return string
     */
    public function getHandlerMethod(string $requestedMethod): string;
}
