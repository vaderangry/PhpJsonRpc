<?php

namespace PhpJsonRpc\Client;

use PhpJsonRpc\Client\ResponseParser\ParserContainer;
use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Core\Result\AbstractResult;
use PhpJsonRpc\Core\Result\Error;
use PhpJsonRpc\Core\Result\Result;
use PhpJsonRpc\Core\ResultSpec;
use PhpJsonRpc\Error\BaseClientException;
use PhpJsonRpc\Error\JsonRpcException;
use PhpJsonRpc\Error\ServerErrorException;
use PhpJsonRpc\Error\InvalidResponseException;

class ResponseParser
{
    /**
     * @var Interceptor
     */
    private $preParse;

    /**
     * ResponseParser constructor.
     */
    public function __construct()
    {
        $this->preParse = Interceptor::createBase();
    }

    /**
     * @param string $payload
     *
     * @return ResultSpec
     */
    public function parse(string $payload): ResultSpec
    {
        $data = @json_decode($payload, true);

        if (!is_array($data)) {
            throw new BaseClientException('Parse error', JsonRpcException::PARSE_ERROR);
        }

        $units = [];

        if ($this->isSingleResponse($data)) {
            $units[] = $this->decodeResult($data);
            return new ResultSpec($units, true);
        }

        /** @var array $data */
        foreach ($data as $response) {
            $units[] = $this->decodeResult($response);
        }

        return new ResultSpec($units, false);
    }

    /**
     * @return Interceptor
     */
    public function onPreParse(): Interceptor
    {
        return $this->preParse;
    }

    /**
     * @param array $record
     *
     * @return AbstractResult
     */
    private function decodeResult(array $record): AbstractResult
    {
        $record = $this->preParse($record);

        if ($this->isValidResult($record)) {
            $unit = new Result($record['id'], $record['result']);
        } elseif ($this->isValidError($record)) {
            $unit = new Error($record['id'], new ServerErrorException($record['error']['message'], $record['error']['code']));
        } else {
            throw new InvalidResponseException();
        }

        return $unit;
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    private function isSingleResponse(array $response): bool
    {
        return array_keys($response) !== range(0, count($response) - 1);
    }

    /**
     * @param array $payload
     *
     * @return bool
     */
    private function isValidResult($payload): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        $headerValid = array_key_exists('jsonrpc', $payload) && $payload['jsonrpc'] === '2.0';
        $resultValid = array_key_exists('result', $payload);
        $idValid     = array_key_exists('id', $payload);

        return $headerValid && $resultValid && $idValid;
    }

    /**
     * @param array $payload
     *
     * @return bool
     */
    private function isValidError($payload): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        $headerValid = array_key_exists('jsonrpc', $payload) && $payload['jsonrpc'] === '2.0';
        $errorValid  = array_key_exists('error', $payload) && is_array($payload['error'])
                       && array_key_exists('code', $payload['error']) && is_int($payload['error']['code'])
                       && array_key_exists('message', $payload['error']) && is_string($payload['error']['message']);
        $idValid     = array_key_exists('id', $payload);

        return $headerValid && $errorValid && $idValid;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function preParse(array $data): array
    {
        $result = $this->preParse->handle(new ParserContainer($this, $data));

        if ($result instanceof ParserContainer) {
            return $result->getValue();
        }

        throw new \RuntimeException();
    }
}
