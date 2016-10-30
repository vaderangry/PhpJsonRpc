<?php

namespace JsonRpc\Core\Call;

class CallUnit extends AbstractCall
{
    /**
     * @var mixed
     */
    private $rawId;

    /**
     * @var string
     */
    private $rawMethod;

    /**
     * @var array
     */
    private $rawParams;

    /**
     * CallUnit constructor.
     *
     * @param mixed  $rawId
     * @param string $rawMethod
     * @param array  $rawParams
     */
    public function __construct($rawId, $rawMethod, array $rawParams)
    {
        $this->rawId     = $rawId;
        $this->rawMethod = $rawMethod;
        $this->rawParams = $rawParams;
    }

    /**
     * @return mixed
     */
    public function getRawId()
    {
        return $this->rawId;
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
