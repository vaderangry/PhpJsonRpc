<?php

namespace PhpJsonRpc\Common\TypeAdapter;

class TypeAdapter
{
    /**
     * @var Rule[]
     */
    private $rules = [];

    /**
     * @param Rule
     *
     * @return $this
     */
    public function register(Rule $rule)
    {
        $this->rules[$rule->getClass()] = $rule;
        return $this;
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function isClassRegistered(string $class): bool
    {
        return array_key_exists($class, $this->rules);
    }

    /**
     * @param mixed $value Array
     *
     * @return mixed Instance of registered class
     */
    public function toObject($value)
    {
        if (!is_array($value)) {
            throw new TypeCastException('Is not array');
        }

        foreach ($this->rules as $class => $rule) {
            if ($this->isMatch($rule, $value)) {
                return $this->createInstance($class, $value);
            }
        }

        throw new TypeCastException('Rule not found');
    }

    /**
     * @param mixed $value Instance of registered class
     *
     * @return array
     */
    public function toArray($value)
    {
        if (!is_object($value)) {
            throw new TypeCastException('Is not object');
        }

        foreach ($this->rules as $class => $rule)
        {
            if (get_class($value) === $class) {
                return $this->createMap($class, $value);
            }
        }

        throw new TypeCastException('Rule not found');
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return count($this->rules) === 0;
    }

    /**
     * @param Rule $expected Transform rule
     * @param array $value   Raw unknown value
     *
     * @return bool
     */
    private function isMatch(Rule $expected, array $value): bool
    {
        return 0 === count(array_diff(array_keys($expected->getReflectedMap()), array_keys($value)));
    }

    /**
     * @param string $class
     * @param array  $data
     *
     * @return object
     */
    private function createInstance(string $class, array $data)
    {
        $rule = $this->rules[$class];

        $reflectionClass = new \ReflectionClass($class);
        $object = $reflectionClass->newInstanceWithoutConstructor();
        $reflectionObject = new \ReflectionObject($object);

        foreach ($rule->getMap() as $property => $key) {
            $reflectionProperty = $reflectionObject->getProperty($property);
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($object, $data[$key]);
        }

        return $object;
    }

    /**
     * @param string $class
     * @param object $object
     *
     * @return array
     */
    private function createMap(string $class, $object)
    {
        $rule = $this->rules[$class];
        $reflectionObject = new \ReflectionObject($object);
        $result = [];

        foreach ($rule->getMap() as $property => $key) {
            $reflectionProperty = $reflectionObject->getProperty($property);
            $reflectionProperty->setAccessible(true);
            $result[$key] = $reflectionProperty->getValue($object);
        }

        return $result;
    }
}
