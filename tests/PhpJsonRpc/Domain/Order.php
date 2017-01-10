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
     * @var \DateTime
     */
    private $createdAt;

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
        $this->createdAt   = new \DateTime('2020-01-01T00:00:00+0300');
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

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
