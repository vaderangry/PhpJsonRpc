<?php

namespace PhpJsonRpc\Tests\Mock;

use PhpJsonRpc\Common\Interceptor\AbstractContainer;

class Container extends AbstractContainer
{
    private $items;

    /**
     * Container constructor.
     *
     * @param array ...$items
     */
    public function __construct(...$items)
    {
        $this->items = $items;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param $index
     *
     * @return mixed|null
     */
    public function getItem($index)
    {
        return $this->items[$index] ?? null;
    }

    /**
     * Get first item of container
     *
     * @return mixed
     */
    public function first()
    {
        return $this->items[0];
    }

    /**
     * Get last item of container
     *
     * @return mixed
     */
    public function last()
    {
        return $this->items[count($this->items) - 1];
    }
}
