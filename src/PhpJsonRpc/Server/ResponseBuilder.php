<?php

namespace PhpJsonRpc\Server;

use PhpJsonRpc\Core\Result\AbstractResult;
use PhpJsonRpc\Core\Result\ResultError;
use PhpJsonRpc\Core\Result\ResultUnit;
use PhpJsonRpc\Core\ResultSpecifier;
use PhpJsonRpc\Error\JsonRpcException;

class ResponseBuilder
{
    /**
     * @param ResultSpecifier $result
     *
     * @return string
     */
    public function build(ResultSpecifier $result): string
    {
        $response = [];
        $units = $result->getResults();

        foreach ($units as $unit) {
            /** @var AbstractResult $unit */
            if ($unit instanceof ResultUnit) {
                /** @var ResultUnit $unit */
                $response[] = [
                    'jsonrpc' => '2.0',
                    'result' => $unit->getResult(),
                    'id'     => $unit->getId()
                ];
            } elseif ($unit instanceof ResultError) {
                /** @var ResultError $unit */
                $baseException = $unit->getBaseException();
                $response[] = [
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code'    => $baseException->getJsonRpcCode(),
                        'message' => $this->getErrorMessage($baseException->getJsonRpcCode()),
                        'data'    => $baseException->getJsonRpcData()
                    ],
                    'id' => null
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
     * @param int $code
     * @return string
     */
    private function getErrorMessage(int $code): string
    {
        switch ($code) {
            case JsonRpcException::PARSE_ERROR:      return 'Parse error';
            case JsonRpcException::INVALID_REQUEST:  return 'Invalid Request';
            case JsonRpcException::METHOD_NOT_FOUND: return 'Method not found';
            case JsonRpcException::INVALID_PARAMS:   return 'Invalid params';
            case JsonRpcException::INTERNAL_ERROR:   return 'Internal error';
            case JsonRpcException::SERVER_ERROR:     return 'Server Error';
        }

        return 'Internal error';
    }
}
