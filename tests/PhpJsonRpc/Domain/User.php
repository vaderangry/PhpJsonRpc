<?php

namespace PhpJsonRpc\Tests;

class User
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $name;

    /**
     * User constructor.
     *
     * @param int    $id
     * @param string $email
     * @param string $name
     */
    public function __construct($id, $email, $name)
    {
        $this->id    = $id;
        $this->email = $email;
        $this->name  = $name;
    }
}
