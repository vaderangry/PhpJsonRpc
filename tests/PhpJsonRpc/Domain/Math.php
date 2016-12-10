<?php

namespace PhpJsonRpc\Tests;

class Math
{
    /**
     * @param int $lhs
     * @param int $rhs
     *
     * @return int
     */
    public function add(int $lhs, int $rhs): int
    {
        return $lhs + $rhs;
    }

    /**
     * @param int $lhs
     * @param int $rhs
     *
     * @return int
     */
    public function multiply($lhs, $rhs = 1)
    {
        return $lhs * $rhs;
    }

    /**
     * @param float $base
     * @param int   $exp
     *
     * @return float
     */
    public function pow(float $base, int $exp): float
    {
        return pow($base, $exp);
    }

    public function error()
    {
        throw new \RuntimeException('Internal error', 999);
    }
}
