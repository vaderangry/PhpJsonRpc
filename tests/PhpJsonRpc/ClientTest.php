<?php

namespace PhpJsonRpc\Tests;

require_once __DIR__ . '/Domain/User.php';
require_once __DIR__ . '/Mock/Transport.php';
require_once __DIR__ . '/Mock/IdGenerator.php';

use PhpJsonRpc\Client;
use PhpJsonRpc\Client\RequestBuilder\BuilderContainer;
use PhpJsonRpc\Client\ResponseParser\ParserContainer;
use PhpJsonRpc\Client\Transport\TransportContainer;
use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Core\Invoke\Invoke;
use PhpJsonRpc\Error\BaseClientException;
use PhpJsonRpc\Error\InvalidResponseException;
use PhpJsonRpc\Error\MethodNotFoundException;
use PhpJsonRpc\Tests\Mock\IdGenerator;
use PhpJsonRpc\Tests\Mock\Transport;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    /**
     * Testing single call
     */
    public function testSingleCall()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new Transport());
        $client->setIdGenerator(new IdGenerator());

        $result = $client->call('Math.pow', [2, 3]);
        $this->assertEquals(8, $result);

        $client->setIdGenerator(new IdGenerator());
        $result = $client->call('Math.pow', [2, 4]);
        $this->assertEquals(16, $result);
    }

    /**
     * Testing single call with server error
     */
    public function testSingleServerError()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new Transport());
        $client->setIdGenerator(new IdGenerator());

        $this->expectException(MethodNotFoundException::class);

        $client->call('Test.serverError', []);
    }

    /**
     * Testing single call with parser error
     */
    public function testSingleParseError()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new Transport());
        $client->setIdGenerator(new IdGenerator());

        $this->expectException(BaseClientException::class);

        $client->call('Test.parseError', []);
    }

    /**
     * Testing batch call
     */
    public function testBatchCall()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new Transport());
        $client->setIdGenerator(new IdGenerator());

        $result = $client->batch()
            ->call('Math.pow', [2, 3])
            ->call('User.getName', ['id' => 8])
            ->batchExecute();

        $this->assertCount(2, $result);

        list($numeric, $name) = $result;

        $this->assertEquals(8, $numeric);
        $this->assertEquals('jaguar', $name);
    }

    /**
     * Testing batch call with invalid response
     */
    public function testBatchInvalidResponse()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new Transport());
        $client->setIdGenerator(new IdGenerator());

        $this->expectException(InvalidResponseException::class);

        $client->batch()
            ->call('Math.pow', [1, 1])
            ->call('User.unknownMethod', [])
            ->call('User.getName', ['id' => 9])
            ->batchExecute();
    }

    /**
     * Testing batch call with server error
     */
    public function testBatchServerError()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new Transport());
        $client->setIdGenerator(new IdGenerator());

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

    /**
     * Testing of RequestBuilder interceptor
     */
    public function testRequestBuilderOnPreBuild()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new Transport());
        $client->setIdGenerator(new IdGenerator());

        $client->getRequestBuilder()->onPreBuild()
            ->add(Interceptor::createWith(function (BuilderContainer $container) {
                $invoke = $container->getInvoke();

                if ($invoke instanceof Invoke && $invoke->getRawMethod() === 'degree') {
                    $invoke = new Invoke($invoke->getRawId(), 'Math.pow', $invoke->getRawParams());
                }

                return new BuilderContainer($container->getBuilder(), $invoke);
            }));

        $result = $client->call('degree', [2, 3]);
        $this->assertEquals(8, $result);
    }

    /**
     * Testing of Transport interceptor
     */
    public function testTransportOnPreRequest()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new Transport());
        $client->setIdGenerator(new IdGenerator());

        $client->getTransport()->onPreRequest()
            ->add(Interceptor::createWith(function (TransportContainer $container) {
                $request = str_replace('[2,3]', '[2,4]', $container->getRequest());
                return new TransportContainer($container->getTransport(), $request);
            }));

        $result = $client->call('Math.pow', [2, 3]);
        $this->assertEquals(16, $result);
    }

    /**
     * Testing of ResponseParser interceptor
     */
    public function testResponseParserOnPreParse()
    {
        $client = new Client('http://localhost');
        $client->setTransport(new Transport());
        $client->setIdGenerator(new IdGenerator());

        $client->getResponseParser()->onPreParse()
            ->add(Interceptor::createWith(function (ParserContainer $container) {
                $response = $container->getValue();
                $result = $response['result'];

                $userMap = ['id', 'email', 'name'];
                // result is user map
                if (is_array($result) && 0 === count(array_diff($userMap, array_keys($result)))) {
                    $response['result'] = new User($result['id'], $result['email'], $result['name']);
                }

                return new ParserContainer($container->getParser(), $response);
            }));

        $user = $client->call('User.getOne', [8]);

        /** @var User $user */
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(8, $user->id);
        $this->assertEquals('vader@angry.mil', $user->email);
        $this->assertEquals('vader', $user->name);
    }
}
