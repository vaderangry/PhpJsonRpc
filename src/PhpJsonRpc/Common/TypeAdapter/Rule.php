<?php

namespace PhpJsonRpc\Common\TypeAdapter;

class Rule
{
    /**
     * @var string
     */
    private $class;

    /**
     * Property => Key
     *
     * @var array
     */
    private $map = [];

    /**
     * Key => Property
     *
     * @var array
     */
    private $reflectedMap = [];

    /**
     * @var array
     */
    private $constructors = [];

    /**
     * @var array
     */
    private $serializers = [];

    /**
     * Rule constructor.
     *
     * @param string $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Create new rule
     *
     * @param string $class
     *
     * @return Rule
     */
    public static function create(string $class): Rule
    {
        return new Rule($class);
    }

    /**
     * Create rule with one-to-one mapping
     *
     * @param string $class
     *
     * @return Rule
     */
    public static function createDefault(string $class): Rule
    {
        $rule = new Rule($class);

        $reflection = new \ReflectionClass($class);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            /** @var \ReflectionProperty $property */
            $rule->assign($property->getName(), $property->getName());
        }

        return $rule;
    }

    /**
     * Simple property-key pair assigning
     *
     * @param string $property Object property
     * @param string $key      Map key
     *
     * @return $this
     */
    public function assign(string $property, string $key)
    {
        $this->doAssign($property, $key);

        return $this;
    }

    /**
     * Assign property constructor
     *
     * @param string   $property
     * @param string   $key
     * @param callable $fx
     *
     * @return $this
     */
    public function assignConstructor(string $property, string $key, callable $fx)
    {
        $this->doAssign($property, $key);
        $this->constructors[$property] = $fx;

        return $this;
    }

    /**
     * Assign property serializer
     *
     * @param string   $property
     * @param string   $key
     * @param callable $fx
     *
     * @return $this
     */
    public function assignSerializer(string $property, string $key, callable $fx)
    {
        $this->doAssign($property, $key);
        $this->serializers[$property] = $fx;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return array
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @return array
     */
    public function getReflectedMap(): array
    {
        return $this->reflectedMap;
    }

    /**
     * @param string $property
     *
     * @return callable|null
     */
    public function getConstructor(string $property)
    {
        return $this->constructors[$property] ?? null;
    }

    /**
     * @param string $property
     *
     * @return callable|null
     */
    public function getSerializer(string $property)
    {
        return $this->serializers[$property] ?? null;
    }

    /**
     * @param string $property
     * @param string $key
     */
    private function doAssign(string $property, string $key)
    {
        $this->map[$property] = $key;
        $this->reflectedMap[$key] = $property;
    }
}
