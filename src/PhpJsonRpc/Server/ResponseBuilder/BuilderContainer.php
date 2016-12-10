<?php

namespace PhpJsonRpc\Server\ResponseBuilder;

use PhpJsonRpc\Common\Interceptor\AbstractContainer;
use PhpJsonRpc\Server\ResponseBuilder;

class BuilderContainer extends AbstractContainer
{
    /**
     * @var ResponseBuilder
     */
    private $builder;

    /**
     * @var mixed
     */
    private $value;

    /**
     * PostBuildContainer constructor.
     *
     * @param ResponseBuilder $builder
     * @param mixed           $value
     */
    public function __construct(ResponseBuilder $builder, $value)
    {
        $this->builder = $builder;
        $this->value   = $value;
    }

    /**
     * @return ResponseBuilder
     */
    public function getBuilder(): ResponseBuilder
    {
        return $this->builder;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
