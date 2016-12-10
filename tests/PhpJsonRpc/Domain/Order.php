<?php

namespace PhpJsonRpc\Tests;

class Order
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $customerId;

    /**
     * Order constructor.
     *
     * @param int    $id
     * @param string $title
     * @param string $description
     * @param int    $customerId
     */
    public function __construct($id, $title, $description, $customerId)
    {
        $this->id          = $id;
        $this->title       = $title;
        $this->description = $description;
        $this->customerId  = $customerId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }
}
