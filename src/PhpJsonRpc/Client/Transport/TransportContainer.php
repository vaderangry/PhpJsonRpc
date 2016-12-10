<?php

namespace PhpJsonRpc\Client\Transport;

use PhpJsonRpc\Client\AbstractTransport;
use PhpJsonRpc\Common\Interceptor\AbstractContainer;

class TransportContainer extends AbstractContainer
{
    /**
     * @var AbstractTransport
     */
    private $transport;

    /**
     * @var string
     */
    private $request;

    /**
     * TransportContainer constructor.
     *
     * @param AbstractTransport $transport
     * @param string            $request
     */
    public function __construct(AbstractTransport $transport, $request)
    {
        $this->transport = $transport;
        $this->request   = $request;
    }

    /**
     * @return AbstractTransport
     */
    public function getTransport(): AbstractTransport
    {
        return $this->transport;
    }

    /**
     * @return string
     */
    public function getRequest(): string
    {
        return $this->request;
    }
}
