<?php

namespace PhpJsonRpc\Server;

use PhpJsonRpc\Common\TypeAdapter\TypeAdapter;
use PhpJsonRpc\Common\TypeAdapter\TypeCastException;
use PhpJsonRpc\Error\InvalidParamsException;
use PhpJsonRpc\Error\ServerErrorException;

/**
 * Invoker of methods
 */
class Invoker
{
    /**
     * @var TypeAdapter
     */
    private $typeAdapter;

    /**
     * Invoker constructor.
     */
    public function __construct()
    {
        $this->typeAdapter = new TypeAdapter();
    }

    /**
     * @param mixed  $object
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function invoke($object, string $method, array $parameters)
    {
        $handler    = $object;
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

        return $this->castResult($result);
    }

    /**
     * @return TypeAdapter
     */
    public function getTypeAdapter(): TypeAdapter
    {
        return $this->typeAdapter;
    }

    /**
     * @param \ReflectionParameter[] $formalParameters Formal parameters
     * @param array                  $parameters       Parameters from request (raw)
     *
     * @return array
     */
    private function prepareActualParameters(array $formalParameters, array $parameters): array
    {
        $result = [];

        // Handle named parameters
        if ($this->isNamedArray($parameters)) {

            foreach ($formalParameters as $formalParameter) {
                /** @var \ReflectionParameter $formalParameter */

                $formalType = (string) $formalParameter->getType();
                $name       = $formalParameter->name;

                if ($formalParameter->isOptional()) {
                    if (!array_key_exists($name, $parameters)) {
                        $result[$name] = $formalParameter->getDefaultValue();
                        continue;
                    }

                    $result[$name] = $formalParameter->getClass() !== null
                        ? $this->toObject($formalParameter->getClass()->name, $parameters[$name])
                        : $this->matchType($formalType, $parameters[$name]);
                }

                if (!array_key_exists($name, $parameters)) {
                    throw new InvalidParamsException('Named parameter error');
                }

                $result[$name] = $this->matchType($formalType, $parameters[$name]);
            }

            return $result;
        }

        // Handle positional parameters
        foreach ($formalParameters as $position => $formalParameter) {
            /** @var \ReflectionParameter $formalParameter */

            if ($formalParameter->isOptional() && !isset($parameters[$position])) {
                break;
            }

            if (!isset($parameters[$position])) {
                throw new InvalidParamsException('Positional parameter error');
            }

            $formalType = (string) $formalParameter->getType();
            $result[] = $formalParameter->getClass() !== null
                ? $this->toObject($formalParameter->getClass()->name, $parameters[$position])
                : $this->matchType($formalType, $parameters[$position]);
        }

        return $result;
    }

    /**
     * @param array $rawParameters
     *
     * @return bool
     */
    private function isNamedArray(array $rawParameters): bool
    {
        return array_keys($rawParameters) !== range(0, count($rawParameters) - 1);
    }

    /**
     * @param string $formalType
     * @param mixed  $value
     *
     * @return mixed
     */
    private function matchType(string $formalType, $value)
    {
        // Parameter without type-hinting returns as is
        if ($formalType === '') {
            return $value;
        }

        if ($this->isType($formalType, $value)) {
            return $value;
        }

        throw new InvalidParamsException('Type hint failed');
    }

    /**
     * @param string $formalClass
     * @param mixed  $value
     *
     * @return mixed
     */
    private function toObject(string $formalClass, $value)
    {
        if ($this->typeAdapter->isClassRegistered($formalClass)) {
            try {
                return $this->typeAdapter->toObject($value);
            } catch (TypeCastException $exception) {
                throw new InvalidParamsException('Class cast failed');
            }
        }

        throw new InvalidParamsException('Class hint failed');
    }

    /**
     * @param string $type
     * @param $value
     *
     * @return bool
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

    /**
     * @param $result
     *
     * @return mixed
     */
    private function castResult($result)
    {
        try {
            $result = $this->typeAdapter->toArray($result);
        } catch (TypeCastException $exception) {
            return $result;
        }

        return $result;
    }
}
