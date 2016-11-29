<?php

namespace PhpJsonRpc\Tests;

use PhpJsonRpc\Client;
use PhpJsonRpc\Error\InvalidResponseException;
use PhpJsonRpc\Error\ParseErrorException;

/**
 * Mock for emulation server response
 */
class MockTransport implements Client\TransportInterface
{
    public function request(string $request): string
    {
        $data = [];

        // Single request
        $data['{"jsonrpc":"2.0","method":"Math.pow","params":[2,3],"id":1}'] = '{"jsonrpc":"2.0","result":8,"id":1}';

        // Single request with Parse error
        $data['{"jsonrpc":"2.0","method":"Test.parseError","params":[],"id":1}'] = '{';

        // Single request with Server error
        $data['{"jsonrpc":"2.0","method":"Test.serverError","params":[],"id":1}'] = '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found"},"id":1}';

        // Batch request
        $data['[{"jsonrpc":"2.0","method":"Math.pow","params":[2,3],"id":1},{"jsonrpc":"2.0","method":"User.getName","params":{"id":8},"id":2}]'] = '[{"jsonrpc":"2.0","result":"jaguar","id":2}, {"jsonrpc":"2.0","result":8,"id":1}]';

        // Batch request with invalid response
        $key = '[' .
            '{"jsonrpc":"2.0","method":"Math.pow","params":[1,1],"id":1},' .
            '{"jsonrpc":"2.0","method":"User.unknownMethod","params":[],"id":2},' .
            '{"jsonrpc":"2.0","method":"User.getName","params":{"id":9},"id":3}'.
            ']';
        $value = '{"jsonrpc":"2.0","error":{"code": -32600,"message":"Invalid Request"},"id":null}';
        $data[$key] = $value;

        // Batch request with Server error
        $key = '[' .
            '{"jsonrpc":"2.0","method":"Math.pow","params":[2,9],"id":1},' .
            '{"jsonrpc":"2.0","method":"User.unknownMethod","params":[],"id":2},' .
            '{"jsonrpc":"2.0","method":"User.getName","params":{"id":9},"id":3}'.
            ']';
        $value = '[' .
            '{"jsonrpc":"2.0","result":512,"id":1},' .
            '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found"},"id":2},' .
            '{"jsonrpc":"2.0","result":"leo","id":3}' .
            ']';
        $data[$key] = $value;

        return $data[$request] ?? '';
    }
}

/**
 * Mock for emulation id generation
 */
class MockIdGenerator implements Client\IdGeneratorInterface
{
    private $current = 1;

    public function get()
    {
        return $this->current++;
    }
}

class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    public function testSingleCall()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new MockTransport());
        $client->setIdGenerator(new MockIdGenerator());

        $result = $client->call('Math.pow', [2, 3]);

        $this->assertEquals(8, $result);
    }

    public function testSingleServerError()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new MockTransport());
        $client->setIdGenerator(new MockIdGenerator());

        $result = $client->call('Test.serverError', []);
        $this->assertNull($result);
    }

    public function testSingleParseError()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new MockTransport());
        $client->setIdGenerator(new MockIdGenerator());

        $this->expectException(ParseErrorException::class);

        $client->call('Test.parseError', []);
    }

    public function testBatchCall()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new MockTransport());
        $client->setIdGenerator(new MockIdGenerator());

        $result = $client->batch()
            ->call('Math.pow', [2, 3])
            ->call('User.getName', ['id' => 8])
            ->batchExecute();

        $this->assertCount(2, $result);

        list($numeric, $name) = $result;

        $this->assertEquals(8, $numeric);
        $this->assertEquals('jaguar', $name);
    }

    public function testBatchInvalidResponse()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new MockTransport());
        $client->setIdGenerator(new MockIdGenerator());

        $this->expectException(InvalidResponseException::class);

        $client->batch()
            ->call('Math.pow', [1, 1])
            ->call('User.unknownMethod', [])
            ->call('User.getName', ['id' => 9])
            ->batchExecute();
    }

    public function testBatchServerError()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new MockTransport());
        $client->setIdGenerator(new MockIdGenerator());

        $result = $client->batch()
            ->call('Math.pow', [2, 9])
            ->call('User.unknownMethod', [])
            ->call('User.getName', ['id' => 9])
            ->batchExecute();

        $this->assertCount(3, $result);

        list($numeric, $unknownMethod, $name) = $result;

        $this->assertEquals(512, $numeric);
        $this->assertNull($unknownMethod);
        $this->assertEquals('leo', $name);
    }
}
