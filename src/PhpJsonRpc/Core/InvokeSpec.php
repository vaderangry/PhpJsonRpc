<?php

namespace PhpJsonRpc\Core;

use PhpJsonRpc\Core\Invoke\AbstractInvoke;

class InvokeSpec
{
    /**
     * @var bool
     */
    private $singleCall = false;

    /**
     * @var array
     */
    private $units;

    /**
     * CallSpecifier constructor.
     *
     * @param AbstractInvoke[] $units
     * @param bool             $singleCall
     */
    public function __construct(array $units, bool $singleCall)
    {
        $this->units      = $units;
        $this->singleCall = $singleCall;
    }

    /**
     * @return boolean
     */
    public function isSingleCall()
    {
        return $this->singleCall;
    }

    /**
     * @return array
     */
    public function getUnits()
    {
        return $this->units;
    }
}
