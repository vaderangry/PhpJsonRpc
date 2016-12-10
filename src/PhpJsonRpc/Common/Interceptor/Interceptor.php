<?php

namespace PhpJsonRpc\Common\Interceptor;

/**
 * Based on "Chain of Responsibility" pattern
 */
class Interceptor
{
    /**
     * @var Interceptor
     */
    private $next;

    /**
     * @var callable
     */
    private $callback;

    /**
     * Chain constructor.
     */
    private function __construct()
    {
        $this->next     = null;
        $this->callback = null;
    }

    /**
     * @return Interceptor
     */
    public static function createBase(): Interceptor
    {
        return new Interceptor();
    }

    /**
     * @param callable $callback
     *
     * @return Interceptor
     */
    public static function createWith(callable $callback): Interceptor
    {
        $element = new Interceptor();
        $element->callback = $callback;

        return $element;
    }

    /**
     * @param Interceptor $element
     *
     * @return $this
     */
    public function add(Interceptor $element)
    {
        if ($this->next) {
            $this->next->add($element);
        } else {
            $this->next = $element;
        }

        return $this;
    }

    /**
     * @param AbstractContainer $container
     *
     * @return mixed
     */
    public function handle(AbstractContainer $container): AbstractContainer
    {
        // Execute callback and pass result next chain
        if (is_callable($this->callback)) {
            $result = call_user_func($this->callback, $container);

            if (!$this->next) {
                return $result;
            }

            return $this->next->handle($result);
        }

        // End of chain
        if (!$this->next) {
            return $container;
        }

        // Execute next chain
        return $this->next->handle($container);
    }
}
