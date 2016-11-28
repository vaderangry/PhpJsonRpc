<?php

namespace Vaderangry\PhpJsonRpc\Core;

use Vaderangry\PhpJsonRpc\Core\Result\AbstractResult;

class ResultSpecifier
{
    /**
     * @var bool
     */
    private $singleResult = false;

    /**
     * @var AbstractResult[]
     */
    private $results;

    /**
     * ResultSpecifier constructor.
     *
     * @param AbstractResult[] $units
     * @param bool             $singleResult
     */
    public function __construct(array $units, bool $singleResult)
    {
        $this->results      = $units;
        $this->singleResult = $singleResult;
    }

    /**
     * @return boolean
     */
    public function isSingleResult()
    {
        return $this->singleResult;
    }

    /**
     * @return AbstractResult[]
     */
    public function getResults()
    {
        return $this->results;
    }
}
