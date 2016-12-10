<?php

namespace PhpJsonRpc\Tests;

require_once __DIR__ . '/Mock/Container.php';

use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Tests\Mock\Container;

class ChainTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    public function testContainer()
    {
        $container = new Container('first', 'second', 1);

        $this->assertEquals('first', $container->first());
        $this->assertEquals('second', $container->getItem(1));
        $this->assertEquals(1, $container->last());
        $this->assertEquals(1, $container->getItem(2));
        $this->assertNull($container->getItem(3));
    }

    public function testEmptyChain()
    {
        $base = Interceptor::createBase();
        $result = $base->handle(new Container(1));
        $this->assertEquals(1, $result->first());

        $base = Interceptor::createBase();
        list($first, $second, $third) = $base->handle(new Container(1, 'second', 'third'))->getItems();

        $this->assertEquals(1, $first);
        $this->assertEquals('second', $second);
        $this->assertEquals('third', $third);
    }

    public function testLongChain()
    {
        $unitTest = $this;

        $base = Interceptor::createBase();

        $base->add(Interceptor::createWith(function(Container $value) {
            return new Container($value->first() * 2);
        }));

        $base->add(Interceptor::createWith(function(Container $value) use ($unitTest) {
            $unitTest->assertEquals(20, $value->first());
            return new Container($value->first() * -1);
        }));

        $base->add(Interceptor::createWith(function(Container $value) use ($unitTest) {
            $unitTest->assertEquals(-20, $value->first());
            return new Container($value->first() * 10);
        }));

        $result = $base->handle(new Container(10));
        $this->assertEquals(-200, $result->first());
    }
}
