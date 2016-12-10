<?php

namespace PhpJsonRpc\Server;

use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Core\Result\AbstractResult;
use PhpJsonRpc\Core\Result\Error;
use PhpJsonRpc\Core\Result\Result;
use PhpJsonRpc\Core\ResultSpec;
use PhpJsonRpc\Error\JsonRpcException;
use PhpJsonRpc\Server\ResponseBuilder\BuilderContainer;

class ResponseBuilder
{
    /**
     * @var Interceptor
     */
    private $preBuild;

    /**
     * ResponseBuilder constructor.
     */
    public function __construct()
    {
        $this->preBuild = Interceptor::createBase();
    }

    /**
     * @param ResultSpec $result
     *
     * @return string
     */
    public function build(ResultSpec $result): string
    {
        $response = [];
        $units = $result->getResults();

        foreach ($units as $unit) {
            /** @var AbstractResult $unit */
            if ($unit instanceof Result) {
                /** @var Result $unit */
                $response[] = [
                    'jsonrpc' => '2.0',
                    'result' => $this->preBuild($unit->getResult()),
                    'id'     => $unit->getId()
                ];
            } elseif ($unit instanceof Error) {
                /** @var Error $unit */
                $baseException = $unit->getBaseException();
                $response[] = [
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code'    => $baseException->getJsonRpcCode(),
                        'message' => $this->getErrorMessage($baseException->getJsonRpcCode()),
                        'data'    => $baseException->getJsonRpcData()
                    ],
                    'id' => $unit->getId()
                ];
            }
        }

        if (empty($response)) {
            return '';
        }

        if ($result->isSingleResult()) {
            return json_encode($response[0]);
        }

        return json_encode($response);
    }

    /**
     * @return Interceptor
     */
    public function onPreBuild(): Interceptor
    {
        return $this->preBuild;
    }

    /**
     * @param mixed $result
     *
     * @return mixed
     */
    private function preBuild($result)
    {
        $container = $this->preBuild->handle(new BuilderContainer($this, $result));

        if ($container instanceof BuilderContainer) {
            return $container->getValue();
        }

        throw new \RuntimeException();
    }

    /**
     * @param int $code
     * @return string
     */
    private function getErrorMessage(int $code): string
    {
        switch ($code) {
            case JsonRpcException::PARSE_ERROR:
                return 'Parse error';

            case JsonRpcException::INVALID_REQUEST:
                return 'Invalid Request';

            case JsonRpcException::METHOD_NOT_FOUND:
                return 'Method not found';

            case JsonRpcException::INVALID_PARAMS:
                return 'Invalid params';

            case JsonRpcException::INTERNAL_ERROR:
                return 'Internal error';

            case JsonRpcException::SERVER_ERROR:
                return 'Server Error';

            default:
                return 'Internal error';
        }
    }
}
