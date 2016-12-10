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
     * Add property-key pair
     *
     * @param string $property
     * @param string $key
     *
     * @return $this
     */
    public function assign(string $property, string $key)
    {
        $this->map[$property] = $key;
        $this->reflectedMap[$key] = $property;

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
}
