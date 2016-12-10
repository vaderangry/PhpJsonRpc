<?php

namespace PhpJsonRpc\Server;

use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Core\Invoke;
use PhpJsonRpc\Core\Invoke\AbstractInvoke;
use PhpJsonRpc\Core\InvokeSpec;
use PhpJsonRpc\Core\Result;
use PhpJsonRpc\Core\ResultSpec;
use PhpJsonRpc\Error\JsonRpcException;
use PhpJsonRpc\Error\MethodNotFoundException;
use PhpJsonRpc\Server\Processor\ProcessorContainer;

class Processor
{
    /**
     * @var array
     */
    private $handlers = [];

    /**
     * @var MapperInterface
     */
    private $mapper;

    /**
     * @var Invoker
     */
    private $invoker;

    /**
     * @var Interceptor
     */
    private $preProcess;

    /**
     * Processor constructor.
     */
    public function __construct()
    {
        $this->mapper     = new Mapper();
        $this->invoker    = new Invoker();
        $this->preProcess = Interceptor::createBase();
    }

    /**
     * @return Invoker
     */
    public function getInvoker(): Invoker
    {
        return $this->invoker;
    }

    /**
     * @param mixed $object
     */
    public function addHandler($object)
    {
        if (!is_object($object)) {
            throw new \DomainException('Expected object');
        }

        $key = get_class($object);
        $this->handlers[$key] = $object;
    }

    /**
     * @param MapperInterface $mapper
     */
    public function setMapper(MapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * @param InvokeSpec $specifier
     *
     * @return ResultSpec
     */
    public function process(InvokeSpec $specifier): ResultSpec
    {
        $resultUnits = [];
        $callUnits   = $specifier->getUnits();

        foreach ($callUnits as $unit) {
            $unit = $this->preProcess($unit);

            if ($unit instanceof Invoke\Invoke) {
                $resultUnits[] = $this->handleCallUnit($unit);
            } elseif ($unit instanceof Invoke\Notification) {
                $resultUnits[] = $this->handleNotificationUnit($unit);
            } else {
                $resultUnits[] = $this->handleErrorUnit($unit);
            }
        }

        return new ResultSpec($resultUnits, $specifier->isSingleCall());
    }

    /**
     * @return Interceptor
     */
    public function onPreProcess(): Interceptor
    {
        return $this->preProcess;
    }

    /**
     * @param AbstractInvoke $invoke
     *
     * @return AbstractInvoke
     */
    private function preProcess(AbstractInvoke $invoke): AbstractInvoke
    {
        $result = $this->preProcess->handle(new ProcessorContainer($this, $invoke));

        if ($result instanceof ProcessorContainer) {
            return $result->getInvoke();
        }

        throw new \RuntimeException();
    }

    /**
     * @param Invoke\Invoke $unit
     * 
     * @return Result\AbstractResult
     */
    private function handleCallUnit(Invoke\Invoke $unit): Result\AbstractResult
    {
        try {
            list($class, $method) = $this->getClassAndMethod($unit->getRawMethod());
            $result = $this->invoker->invoke($this->handlers[$class], $method, $unit->getRawParams());
        } catch (JsonRpcException $exception) {
            return new Result\Error($unit->getRawId(), $exception);
        }

        return new Result\Result($unit->getRawId(), $result);
    }

    /**
     * @param Invoke\Notification $unit
     * 
     * @return Result\AbstractResult
     */
    private function handleNotificationUnit(Invoke\Notification $unit): Result\AbstractResult
    {
        try {
            list($class, $method) = $this->getClassAndMethod($unit->getRawMethod());
            $this->invoker->invoke($this->handlers[$class], $method, $unit->getRawParams());
        } catch (JsonRpcException $exception) {
            return new Result\Error(null, $exception);
        }

        return new Result\Notification();
    }

    /**
     * @param Invoke\Error $unit
     * 
     * @return Result\AbstractResult
     */
    private function handleErrorUnit(Invoke\Error $unit): Result\AbstractResult
    {
        return new Result\Error(null, $unit->getBaseException());
    }

    /**
     * @param string $requestedMethod
     *
     * @return array
     */
    private function getClassAndMethod(string $requestedMethod)
    {
        list($class, $method) = $this->mapper->getClassAndMethod($requestedMethod);

        if ($class && array_key_exists($class, $this->handlers) && method_exists($this->handlers[$class], $method)) {
            return [$class, $method];
        }

        throw new MethodNotFoundException();
    }
}
