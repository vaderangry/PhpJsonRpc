<?php

namespace PhpJsonRpc\Core\Invoke;

class Notification extends AbstractInvoke
{
    /**
     * @var string
     */
    private $rawMethod;

    /**
     * @var array
     */
    private $rawParams;

    /**
     * NotificationUnit constructor.
     *
     * @param string $method
     * @param array  $params
     */
    public function __construct($method, array $params)
    {
        $this->rawMethod = $method;
        $this->rawParams = $params;
    }

    /**
     * @return string
     */
    public function getRawMethod(): string
    {
        return $this->rawMethod;
    }

    /**
     * @return array
     */
    public function getRawParams(): array
    {
        return $this->rawParams;
    }
}
