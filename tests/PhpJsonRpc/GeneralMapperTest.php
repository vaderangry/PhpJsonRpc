<?php

namespace PhpJsonRpc\Tests;

use PhpJsonRpc\Server\GeneralMapper;

class GeneralMapperTest extends \PHPUnit_Framework_TestCase
{
    public function testGeneralMapper()
    {
        $mapper = new GeneralMapper();
        list($class, $method) = $mapper->getClassAndMethod("TestHandler.add");

        $this->assertEquals("TestHandler", $class);
        $this->assertEquals("add", $method);
    }
}
