<?php

namespace PhpJsonRpc\Tests;

require_once __DIR__ . '/Domain/User.php';
require_once __DIR__ . '/Domain/UserRepository.php';
require_once __DIR__ . '/Domain/Math.php';
require_once __DIR__ . '/Mock/Mapper.php';
require_once __DIR__ . '/Mock/RequestProvider.php';

use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Common\TypeAdapter\Rule;
use PhpJsonRpc\Core\Invoke\Invoke;
use PhpJsonRpc\Server\Processor\ProcessorContainer;
use PhpJsonRpc\Server\RequestParser\ParserContainer;
use PhpJsonRpc\Server\ResponseBuilder\BuilderContainer;
use PhpJsonRpc\Server;
use PhpJsonRpc\Tests\Mock\Mapper;
use PhpJsonRpc\Tests\Mock\RequestProvider;

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
        $provider = new RequestProvider($request);
        $server   = new Server();
        $server->setRequestProvider($provider);

        // Create instances of handlers
        $repository = new UserRepository();
        $math       = new Math();

        $response = $server
            ->addHandler($repository)
            ->addHandler($math)
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
        $provider = new RequestProvider($request);
        $server   = new Server();
        $server->setRequestProvider($provider);

        // Add custom mapper
        $mapper = new Mapper();
        $server->setMapper($mapper);

        // Create instances of handlers
        $repository = new UserRepository();
        $math       = new Math();

        $response = $server
            ->addHandler($math)
            ->addHandler($repository)
            ->execute();

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * Testing of RequestParser interceptor
     */
    public function testParserOnPreParse()
    {
        $provider = new RequestProvider('{"jsonrpc": "2.0", "method": "User.getOne", "params": {"id": 999}, "id": 10}');
        $server   = new Server();
        $server->setRequestProvider($provider);

        // Add custom mapper
        $mapper = new Mapper();
        $server->setMapper($mapper);

        $server->getRequestParser()->onPreParse()
            ->add(Interceptor::createWith(function (ParserContainer $container) {
                $parser  = $container->getParser();
                $request = $container->getValue();

                $request['params']['id'] = 777;

                return new ParserContainer($parser, $request);
            }));

        // Create instance of handlers
        $repository = new UserRepository();

        $response = $server->addHandler($repository)->execute();

        $this->assertEquals('{"jsonrpc":"2.0","result":{"id":777,"email":"unknown@empire.com","name":"unknown"},"id":10}', $response);
    }

    /**
     * Testing of Processor interceptor
     */
    public function testProcessorOnPreProcess()
    {
        $provider = new RequestProvider('{"jsonrpc": "2.0", "method": "User.getOne", "params": {"id": 999}, "id": 10}');
        $server   = new Server();
        $server->setRequestProvider($provider);

        // Add custom mapper
        $mapper = new Mapper();
        $server->setMapper($mapper);

        $server->getProcessor()->onPreProcess()
            ->add(Interceptor::createWith(function (ProcessorContainer $container) {
                $invoke = $container->getInvoke();

                if ($invoke instanceof Invoke && $invoke->getRawMethod() === 'User.getOne') {
                    $invoke = new Invoke($invoke->getRawId(), $invoke->getRawMethod(), ['id' => 888]);
                }

                return new ProcessorContainer($container->getProcessor(), $invoke);
            }));

        // Create instance of handlers
        $repository = new UserRepository();

        $response = $server->addHandler($repository)->execute();

        $this->assertEquals('{"jsonrpc":"2.0","result":{"id":888,"email":"unknown@empire.com","name":"unknown"},"id":10}', $response);
    }

    /**
     * Testing of ResponseBuilder interceptor
     */
    public function testBuilderOnPreBuild()
    {
        $provider = new RequestProvider('{"jsonrpc": "2.0", "method": "User.getOne", "params": {"id": 999}, "id": 10}');
        $server   = new Server();
        $server->setRequestProvider($provider);

        // Add custom mapper
        $mapper = new Mapper();
        $server->setMapper($mapper);

        $server->getResponseBuilder()->onPreBuild()
            ->add(Interceptor::createWith(function (BuilderContainer $container) {
                $builder = $container->getBuilder();
                $value   = $container->getValue();

                // Custom type mapping
                if ($value instanceof User) {
                    /** @var User $value */
                    $value = [
                        'id'   => $value->id,
                        'name' => $value->name
                    ];
                }

                return new BuilderContainer($builder, $value);
            }));

        // Create instance of handlers
        $repository = new UserRepository();

        $response = $server->addHandler($repository)->execute();

        $this->assertEquals('{"jsonrpc":"2.0","result":{"id":999,"name":"unknown"},"id":10}', $response);
    }

    public function testTypeCastingResult()
    {
        $provider = new RequestProvider('{"jsonrpc": "2.0", "method": "User.getOne", "params": {"id": 999}, "id": 10}');
        $server   = new Server();
        $server->setRequestProvider($provider);

        // Add custom mapper
        $mapper = new Mapper();
        $server->setMapper($mapper);

        $server->getTypeAdapter()
            ->register(
                Rule::create(User::class)
                    ->assign('id', 'uid')
                    ->assign('email', 'mail')
                    ->assign('name', 'alias')
            );

        // Create instance of handlers
        $repository = new UserRepository();

        $response = $server->addHandler($repository)->execute();

        $this->assertEquals('{"jsonrpc":"2.0","result":{"uid":999,"mail":"unknown@empire.com","alias":"unknown"},"id":10}', $response);
    }

    public function testTypeCastingInvoke()
    {
        $provider = new RequestProvider('{"jsonrpc": "2.0", "method": "User.create", "params": [{"id": null, "email":"email@test.com","name":"name"}], "id": 10}');
        $server   = new Server();
        $server->setRequestProvider($provider);

        // Add custom mapper
        $mapper = new Mapper();
        $server->setMapper($mapper);

        $server->getTypeAdapter()
            ->register(Rule::createDefault(User::class));

        // Create instance of handlers
        $repository = new UserRepository();

        $response = $server->addHandler($repository)->execute();

        $this->assertEquals('{"jsonrpc":"2.0","result":{"id":8,"email":"email@test.com","name":"name"},"id":10}', $response);
    }

    public function generalRequestsProvider()
    {
        return [
            // #0 Rpc call with positional parameters
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.add", "params": [16, 32], "id": 1}',
                '{"jsonrpc":"2.0","result":48,"id":1}'
            ],
            // #1 Rpc call with named parameters
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.add", "params": {"lhs": 2, "rhs": 16}, "id": 10}',
                '{"jsonrpc":"2.0","result":18,"id":10}'
            ],
            // #1.1 Rpc call with named parameters
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.add", "params": {"lhs": 2, "rhs": null}, "id": 10}',
                '{"jsonrpc":"2.0","result":2,"id":10}'
            ],
            // #2
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.multiply", "params": {"lhs": 2, "rhs": 2}, "id": 20}',
                '{"jsonrpc":"2.0","result":4,"id":20}'
            ],
            // #3 Rpc call with invalid parameters (invalid params)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.add", "params": {"lhs": 2, "rhs": "bad"}, "id": 30}',
                '{"jsonrpc":"2.0","error":{"code":-32602,"message":"Invalid params","data":{"code":0,"message":"Type hint failed"}},"id":30}'
            ],
            // #4 Rpc call with invalid JSON (parse error)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.add", "params": {"lhs": 2, "rhs": 3}, "id}',
                '{"jsonrpc":"2.0","error":{"code":-32700,"message":"Parse error","data":{"code":0,"message":""}},"id":null}'
            ],
            // #5 Rpc call non-existent class (method not found)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.TestBetaHandler.add", "params": {"lhs": 2, "rhs": 3}, "id": 50}',
                '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found","data":{"code":0,"message":""}},"id":50}'
            ],
            // #6 Rpc call non-existent method (method not found)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.division", "params": {"lhs": 2, "rhs": 16}, "id": 60}',
                '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found","data":{"code":0,"message":""}},"id":60}'
            ],
            // #7 Rpc call with invalid Request object (invalid request)
            [
                '{"jsonrpc": "2.0", "params": {"lhs": 2, "rhs": 16}, "id": 10}',
                '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request","data":{"code":0,"message":""}},"id":null}'
            ],
            // #8 Notification
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.add", "params": [18,18]}',
                ''
            ],
            // #9 Rpc call with server error (server error)
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.error", "params": [], "id": 90}',
                '{"jsonrpc":"2.0","error":{"code":-32000,"message":"Server Error","data":{"code":999,"message":"Internal error"}},"id":90}'
            ],
            // #10 Batch request
            [
                '[' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.add", "params": [1,2], "id": "101"},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.pow", "params": [7,3], "id": "102"},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.multiply", "params": [42,23]},' .
                    '{"foo": "boo"},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.pow", "params": {"base": 3, "exp": 7}, "id": "105"},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.error", "id": "106"}' .
                ']',
                '[' .
                    '{"jsonrpc":"2.0","result":3,"id":"101"},' .
                    '{"jsonrpc":"2.0","result":343,"id":"102"},' .
                    '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request","data":{"code":0,"message":""}},"id":null},' .
                    '{"jsonrpc":"2.0","result":2187,"id":"105"},' .
                    '{"jsonrpc":"2.0","error":{"code":-32000,"message":"Server Error","data":{"code":999,"message":"Internal error"}},"id":"106"}' .
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
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.add", "params": [1,2]},' .
                    '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.Math.pow", "params": [7,3]}' .
                ']',
                ''
            ],
            // #15 Rpc call with skipping default arguments
            [
                '{"jsonrpc": "2.0", "method": "PhpJsonRpc.Tests.UserRepository.getList", "params": {"limit": 20}, "id": 150}',
                '{"jsonrpc":"2.0","result":[' .
                    '{"id":1,"email":"vader@@empire.com","name":"vader"},' .
                    '{"id":2,"email":"yoda@empire.com","name":"yoda"},' .
                    '{"id":3,"email":"obiwan@empire.com","name":"obiwan"},' .
                    '{"id":4,"email":"bobafett@empire.com","name":"bobafett"},' .
                    '{"id":5,"email":"amidala@empire.com","name":"amidala"}' .
                '],"id":150}'
            ]
        ];
    }

    public function mapperRequestsProvider()
    {
        return [
            // # 0
            [
                '{"jsonrpc": "2.0", "method": "User.getOne", "params": [1], "id": 10}',
                '{"jsonrpc":"2.0","result":{"id":1,"email":"unknown@empire.com","name":"unknown"},"id":10}'
            ],
            // # 1
            [
                '{"jsonrpc": "2.0", "method": "Math.pow", "params": [2, 8], "id": 20}',
                '{"jsonrpc":"2.0","result":256,"id":20}'
            ],
            // # 2
            [
                '{"jsonrpc": "2.0", "method": "User.getList", "id": 30}',
                '{"jsonrpc":"2.0","result":[' .
                    '{"id":1,"email":"vader@@empire.com","name":"vader"},' .
                    '{"id":2,"email":"yoda@empire.com","name":"yoda"},' .
                    '{"id":3,"email":"obiwan@empire.com","name":"obiwan"},' .
                    '{"id":4,"email":"bobafett@empire.com","name":"bobafett"},' .
                    '{"id":5,"email":"amidala@empire.com","name":"amidala"}' .
                '],"id":30}'
            ]
        ];
    }
}
