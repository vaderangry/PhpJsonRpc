<?php

namespace JsonRpc\Server;

class GeneralMapper implements MapperInterface
{
    /**
     * @param string $requestedMethod
     * @return string
     */
    public function getHandlerClass(string $requestedMethod): string
    {
        $chunks = explode('.', $requestedMethod);
        $class  = array_slice($chunks, 0, -1);
        return implode('\\', $class);
    }

    /**
     * @param string $requestedMethod
     * @return string
     */
    public function getHandlerMethod(string $requestedMethod): string
    {
        $chunks = explode('.', $requestedMethod);
        $method = end($chunks);
        return $method;
    }
}
