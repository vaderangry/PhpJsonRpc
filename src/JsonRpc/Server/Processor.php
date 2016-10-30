<?php

namespace JsonRpc\Server;

use JsonRpc\Core\Call\CallUnit;
use JsonRpc\Core\Call\CallError;
use JsonRpc\Core\Call\CallNotification;
use JsonRpc\Core\CallSpecifier;
use JsonRpc\Core\Result\AbstractResult;
use JsonRpc\Core\Result\ResultError;
use JsonRpc\Core\Result\ResultNotification;
use JsonRpc\Core\Result\ResultUnit;
use JsonRpc\Core\ResultSpecifier;
use JsonRpc\Error\InvalidParamsException;
use JsonRpc\Error\JsonRpcException;
use JsonRpc\Error\MethodNotFoundException;
use JsonRpc\Error\ServerErrorException;

class Processor
{
    /**
     * @var array
     */
    private $handlers = [];

    /**
     * @var MapperInterface[]
     */
    private $mappers = [];

    /**
     * Processor constructor.
     */
    public function __construct()
    {
        $this->mappers[] = new GeneralMapper();
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
    public function addMapper(MapperInterface $mapper)
    {
        $this->mappers[] = $mapper;
    }

    /**
     * @param CallSpecifier $specifier
     * @return ResultSpecifier
     */
    public function process(CallSpecifier $specifier): ResultSpecifier
    {
        if (count($this->mappers) === 0) {
            throw new \LogicException('Mappers not found');
        }

        $resultUnits = [];
        $callUnits   = $specifier->getUnits();

        foreach ($callUnits as $unit) {
            if ($unit instanceof CallUnit) {
                $resultUnits[] = $this->handleCallUnit($unit);
            } elseif ($unit instanceof CallNotification) {
                $resultUnits[] = $this->handleNotificationUnit($unit);
            } else {
                $resultUnits[] = $this->handleErrorUnit($unit);
            }
        }

        return new ResultSpecifier($resultUnits, $specifier->isSingleCall());
    }

    /**
     * @param CallUnit $unit
     * @return AbstractResult
     */
    private function handleCallUnit(CallUnit $unit): AbstractResult
    {
        try {
            list($class, $method) = $this->getClassAndMethod($unit->getRawMethod());
            $result = $this->invoke($class, $method, $unit->getRawParams());
        } catch (JsonRpcException $exception) {
            return new ResultError($exception);
        }

        return new ResultUnit($unit->getRawId(), $result);
    }

    /**
     * @param CallNotification $unit
     * @return AbstractResult
     */
    private function handleNotificationUnit(CallNotification $unit): AbstractResult
    {
        try {
            list($class, $method) = $this->getClassAndMethod($unit->getRawMethod());
            $this->invoke($class, $method, $unit->getRawParams());
        } catch (JsonRpcException $exception) {
            return new ResultError($exception);
        }

        return new ResultNotification();
    }

    /**
     * @param CallError $unit
     * @return AbstractResult
     */
    private function handleErrorUnit(CallError $unit): AbstractResult
    {
        return new ResultError($unit->getBaseException());
    }

    /**
     * @param string $requestedMethod
     * @return array
     * @throws MethodNotFoundException
     */
    private function getClassAndMethod(string $requestedMethod)
    {
        foreach ($this->mappers as $mapper) {
            /** @var MapperInterface $mapper */
            $class  = $mapper->getHandlerClass($requestedMethod);
            $method = $mapper->getHandlerMethod($requestedMethod);

            if ($class && array_key_exists($class, $this->handlers) && method_exists($this->handlers[$class], $method)) {
                return [$class, $method];
            }
        }

        throw new MethodNotFoundException();
    }

    /**
     * @param string $class
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws InvalidParamsException
     * @throws ServerErrorException
     */
    private function invoke(string $class, string $method, array $parameters)
    {
        $handler    = $this->handlers[$class];
        $reflection = new \ReflectionMethod($handler, $method);

        if ($reflection->getNumberOfRequiredParameters() > count($parameters)) {
            throw new InvalidParamsException('Expected ' . $reflection->getNumberOfRequiredParameters() . ' parameters');
        }

        $formalParameters = $reflection->getParameters();
        $actualParameters = $this->prepareActualParameters($formalParameters, $parameters);

        try {
            $result = $reflection->invokeArgs($handler, $actualParameters);
        } catch (\Exception $exception) {
            throw new ServerErrorException($exception->getMessage(), $exception->getCode());
        }

        return $result;
    }

    /**
     * @param \ReflectionParameter[] $formalParameters Formal parameters
     * @param array                  $parameters       Parameters from request (raw)
     * @return array
     * @throws InvalidParamsException
     */
    private function prepareActualParameters(array $formalParameters, array $parameters): array
    {
        $result = [];

        // Handle named parameters
        if ($this->isNamedParameters($parameters)) {

            foreach ($formalParameters as $formalParameter) {
                /** @var \ReflectionParameter $formalParameter */

                $formalType = (string) $formalParameter->getType();
                $name       = $formalParameter->getName();

                if ($formalParameter->isOptional()) {
                    if (array_key_exists($name, $parameters)) {
                        $result[$name] = $this->matchType($formalType, $parameters[$name]);
                    } else {
                        continue;
                    }
                }

                if (!array_key_exists($name, $parameters)) {
                    throw new InvalidParamsException('Named parameter error');
                }

                $result[$name] = $this->matchType($formalType, $parameters[$name]);
            }

            return $result;
        }

        // Handle positional parameters
        for ($position = 0; $position < count($formalParameters); $position++) {
            /** @var \ReflectionParameter $formalParameter */
            $formalParameter = $formalParameters[$position];

            if ($formalParameter->isOptional() && !isset($parameters[$position])) {
                break;
            }

            if (!isset($parameters[$position])) {
                throw new InvalidParamsException('Positional parameter error');
            }

            $formalType = (string) $formalParameter->getType();
            $result[] = $this->matchType($formalType, $parameters[$position]);
        }

        return $result;
    }

    /**
     * @param array $rawParameters
     * @return bool
     */
    private function isNamedParameters(array $rawParameters): bool
    {
        return array_keys($rawParameters) !== range(0, count($rawParameters) - 1);
    }

    /**
     * @param string $formalType
     * @param mixed $value
     * @return mixed
     * @throws InvalidParamsException
     */
    private function matchType($formalType, $value)
    {
        // Parameter without type-hinting returns as is
        if ($formalType === '') {
            return $value;
        }

        if ($this->isType($formalType, $value)) {
            return $value;
        }

        throw new InvalidParamsException('Match type failed');
    }

    /**
     * @param string $type
     * @param $value
     * @return bool
     * @throws InvalidParamsException
     */
    private function isType(string $type, $value)
    {
        switch ($type) {
            case 'bool':
                return is_bool($value);

            case 'int':
                return is_int($value);

            case 'float':
                return is_float($value) || is_int($value);

            case 'string':
                return is_string($value);

            case 'array':
                return is_array($value);

            default:
                throw new InvalidParamsException('Type match error');
        }
    }
}
