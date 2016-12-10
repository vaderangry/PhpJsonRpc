<?php

namespace PhpJsonRpc\Client\RequestBuilder;

use PhpJsonRpc\Client\RequestBuilder;
use PhpJsonRpc\Common\Interceptor\AbstractContainer;
use PhpJsonRpc\Core\Invoke\AbstractInvoke;

class BuilderContainer extends AbstractContainer
{
    /**
     * @var RequestBuilder
     */
    private $builder;

    /**
     * @var AbstractInvoke
     */
    private $invoke;

    /**
     * BuilderContainer constructor.
     *
     * @param RequestBuilder $builder
     * @param AbstractInvoke $invoke
     */
    public function __construct(RequestBuilder $builder, AbstractInvoke $invoke)
    {
        $this->builder = $builder;
        $this->invoke  = $invoke;
    }

    /**
     * @return RequestBuilder
     */
    public function getBuilder(): RequestBuilder
    {
        return $this->builder;
    }

    /**
     * @return AbstractInvoke
     */
    public function getInvoke(): AbstractInvoke
    {
        return $this->invoke;
    }
}
