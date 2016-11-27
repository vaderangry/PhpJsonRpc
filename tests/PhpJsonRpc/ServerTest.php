<?php

namespace PhpJsonRpc;

use PhpJsonRpc\Server\MapperInterface;

class HandlerExampleAlpha
{
    /**
     * @param int $lhs
     * @param int $rhs
     * @return int
     */
    public function add(int $lhs, int $rhs): int
    {
        return $lhs + $rhs;
    }

    /**
     * @param int $lhs
     * @param int $rhs
     * @return int
     */
    public function multiply($lhs, $rhs = 1)
    {
        return $lhs * $rhs;
    }

    public function error()
    {
        throw new \RuntimeException('Internal error', 999);
    }
}

class HandlerExampleOmega
{
    /**
     * @param float $base
     * @param int $exp
     * @return float
     */
    public function pow(float $base, int $exp): float
    {
        return pow($base, $exp);
    }
}

class UserExample
{
    /**
     * @return int ID of created user
     */
    public function create()
    {
        return 256;
    }

    /**
     * @return bool
     */
    public function block()
    {
        return true;
    }
}

class CustomMapper implements MapperInterface
{
    public function getClassAndMethod(string $requestedMethod): array
    {
        $map = [
            'User.Profile.create' => [UserExample::class, 'create'],
            'User.Profile.block'  => [UserExample::class, 'block']
        ];

        if (array_key_exists($requestedMethod, $map)) {
            return $map[$requestedMethod];
        }

        return ['', ''];
    }
}

class ServerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    /**
     * General test of default configuration
     *
     * @dataProvider generalRequestsProvider
     *
     * @param string $request
     * @param string $expectedResponse
     */
    public function testExecute(string $request, string $expectedResponse)
    {
        $server = new Server($request);

        $response = $server
            ->addHandler(new HandlerExampleAlpha())
            ->addHandler(new HandlerExampleOmega())
            ->execute();

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * Testing of custom mapper
     *
     * @dataProvider mapperRequestsProvider
     *
     * @param string $request
     * @param string $expectedResponse
     */
    public function testCustomMapper(string $request, string $expectedResponse)
    {
        $server = new Server($request);

        $response = $server
            ->addMapper(new CustomMapper())
            ->addHandler(new HandlerExampleAlpha())
            ->addHandler(new HandlerExampleOmega())
            ->addHandler(new UserExample())
            ->execute();

        $this->assertEquals($expectedResponse, $response);
    }

    public function generalRequestsProvider()
    {
        return [
            // #0 Rpc call with positional parameters
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.add", "params": [16, 32], "id": 1}',
                '{"jsonrpc":"2.0","result":48,"id":1}'
            ],
            // #1 Rpc call with named parameters
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.add", "params": {"lhs": 2, "rhs": 16}, "id": 10}',
                '{"jsonrpc":"2.0","result":18,"id":10}'
            ],
            // #2
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.multiply", "params": {"lhs": 2, "rhs": 2}, "id": 20}',
                '{"jsonrpc":"2.0","result":4,"id":20}'
            ],
            // #3 Rpc call with invalid parameters (invalid params)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.add", "params": {"lhs": 2, "rhs": "bad"}, "id": 30}',
                '{"jsonrpc":"2.0","error":{"code":-32602,"message":"Invalid params","data":{"code":0,"message":"Match type failed"}},"id":null}'
            ],
            // #4 Rpc call with invalid JSON (parse error)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.add", "params": {"lhs": 2, "rhs": 3}, "id}',
                '{"jsonrpc":"2.0","error":{"code":-32700,"message":"Parse error","data":{"code":0,"message":""}},"id":null}'
            ],
            // #5 Rpc call non-existent class (method not found)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.TestBetaHandler.add", "params": {"lhs": 2, "rhs": 3}, "id": 50}',
                '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found","data":{"code":0,"message":""}},"id":null}'
            ],
            // #6 Rpc call non-existent method (method not found)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.division", "params": {"lhs": 2, "rhs": 16}, "id": 60}',
                '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found","data":{"code":0,"message":""}},"id":null}'
            ],
            // #7 Rpc call with invalid Request object (invalid request)
            [
                '{"jsonrpc": "2.0", "params": {"lhs": 2, "rhs": 16}, "id": 10}',
                '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request","data":{"code":0,"message":""}},"id":null}'
            ],
            // #8 Notification
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.add", "params": [18,18]}',
                ''
            ],
            // #9 Rpc call with server error (server error)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.error", "params": [], "id": 90}',
                '{"jsonrpc":"2.0","error":{"code":-32000,"message":"Server Error","data":{"code":999,"message":"Internal error"}},"id":null}'
            ],
            // #10 Batch request
            [
                '[' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.add", "params": [1,2], "id": "101"},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleOmega.pow", "params": [7,3], "id": "102"},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.multiply", "params": [42,23]},' .
                    '{"foo": "boo"},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleOmega.pow", "params": {"base": 3, "exp": 7}, "id": "105"},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.error", "id": "106"}' .
                ']',
                '[' .
                    '{"jsonrpc":"2.0","result":3,"id":"101"},' .
                    '{"jsonrpc":"2.0","result":343,"id":"102"},' .
                    '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request","data":{"code":0,"message":""}},"id":null},' .
                    '{"jsonrpc":"2.0","result":2187,"id":"105"},' .
                    '{"jsonrpc":"2.0","error":{"code":-32000,"message":"Server Error","data":{"code":999,"message":"Internal error"}},"id":null}' .
                ']'
            ],
            // # 11 Rpc call with invalid batch
            [
                '[]',
                '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request","data":{"code":0,"message":""}},"id":null}'
            ],
            // # 12
            [
                '[1]',
                '[{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request","data":{"code":0,"message":""}},"id":null}]'
            ],
            // # 13
            [
                '[1, 2, 3]',
                '[' .
                    '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request","data":{"code":0,"message":""}},"id":null},' .
                    '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request","data":{"code":0,"message":""}},"id":null},' .
                    '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request","data":{"code":0,"message":""}},"id":null}' .
                ']'
            ],
            // # 14 Rpc call Batch (all notifications):
            [
                '[' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.add", "params": [1,2]},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleOmega.pow", "params": [7,3]}' .
                ']',
                ''
            ]
        ];
    }

    public function mapperRequestsProvider()
    {
        return [
            [
                '{"jsonrpc": "2.0", "method": "User.Profile.create", "id": 10}',
                '{"jsonrpc":"2.0","result":256,"id":10}'
            ],
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.HandlerExampleAlpha.add", "params": [16, 32], "id": 20}',
                '{"jsonrpc":"2.0","result":48,"id":20}'
            ],
            [
                '{"jsonrpc": "2.0", "method": "User.Profile.block", "id": 30}',
                '{"jsonrpc":"2.0","result":true,"id":30}'
            ]
        ];
    }
}
