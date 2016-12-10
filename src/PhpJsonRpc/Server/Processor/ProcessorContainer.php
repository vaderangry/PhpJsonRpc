<?php

namespace PhpJsonRpc\Server\Processor;

use PhpJsonRpc\Common\Interceptor\AbstractContainer;
use PhpJsonRpc\Core\Invoke\AbstractInvoke;
use PhpJsonRpc\Server\Processor;

class ProcessorContainer extends AbstractContainer
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var AbstractInvoke
     */
    private $invoke;

    /**
     * ProcessorContainer constructor.
     *
     * @param Processor      $processor
     * @param AbstractInvoke $invoke
     */
    public function __construct(Processor $processor, AbstractInvoke $invoke)
    {
        $this->processor = $processor;
        $this->invoke    = $invoke;
    }

    /**
     * @return Processor
     */
    public function getProcessor(): Processor
    {
        return $this->processor;
    }

    /**
     * @return AbstractInvoke
     */
    public function getInvoke(): AbstractInvoke
    {
        return $this->invoke;
    }
}
