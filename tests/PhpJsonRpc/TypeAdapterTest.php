<?php

namespace PhpJsonRpc\Tests;

require_once __DIR__ . '/Domain/User.php';
require_once __DIR__ . '/Domain/Order.php';

use PhpJsonRpc\Common\TypeAdapter\Rule;
use PhpJsonRpc\Common\TypeAdapter\TypeAdapter;
use PhpJsonRpc\Common\TypeAdapter\TypeCastException;

class TypeAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    public function testSingleCastToArray()
    {
        $caster = new TypeAdapter();

        $caster->register(
            Rule::create(User::class)
                ->assign('name', 'x')
                ->assign('email', 'y')
                ->assign('id', 'z')
        );

        $result = $caster->toArray(new User('8', 'vader@angry.com', 'vader'));

        $this->assertCount(3, $result);
        $this->assertArraySubset(['x' => 'vader', 'y' => 'vader@angry.com', 'z' => '8'], $result);
    }

    public function testSingleCastToObject()
    {
        $caster = new TypeAdapter();

        $caster->register(
            Rule::create(User::class)
                ->assign('name', 'x')
                ->assign('email', 'y')
                ->assign('id', 'z')
        );

        $result = $caster->toObject(['x' => 'vader', 'y' => 'vader@angry.com', 'z' => '8']);

        /** @var User $result */
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('8', $result->id);
        $this->assertEquals('vader@angry.com', $result->email);
        $this->assertEquals('vader', $result->name);
    }

    public function testSingleCastToArrayError()
    {
        $caster = new TypeAdapter();

        $this->expectException(TypeCastException::class);
        $this->expectExceptionMessage('Rule not found');

        $caster->toArray(new User('8', 'vader@angry.com', 'vader'));
    }

    public function testSingleCastToObjectError()
    {
        $caster = new TypeAdapter();

        $this->expectException(TypeCastException::class);
        $this->expectExceptionMessage('Rule not found');

        $caster->toObject(['x' => 'vader', 'y' => 'vader@angry.com', 'z' => '8']);
    }

    public function testMultiCastToArray()
    {
        $caster = new TypeAdapter();

        $caster->register(
            Rule::create(User::class)
                ->assign('name', 'x')
                ->assign('email', 'y')
                ->assign('id', 'z')
        );

        $caster->register(
            Rule::createDefault(Order::class) // Create default configuration
                ->assign('id', 'number')      // Rewrite one pair of default configuration
        );

        $result = $caster->toArray(new User('8', 'vader@angry.com', 'vader'));

        $this->assertCount(3, $result);
        $this->assertArraySubset(['x' => 'vader', 'y' => 'vader@angry.com', 'z' => '8'], $result);

        $result = $caster->toArray(new Order(1, 'Test title', 'Test description', 8));

        $this->assertCount(4, $result);
        $this->assertArraySubset(['number' => 1, 'title' => 'Test title', 'description' => 'Test description', 'customerId' => 8], $result);
    }
}
