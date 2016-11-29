<?php

namespace PhpJsonRpc\Client;

use PhpJsonRpc\Core\Result\ResultError;
use PhpJsonRpc\Core\Result\ResultUnit;
use PhpJsonRpc\Core\ResultSpecifier;
use PhpJsonRpc\Error\ServerErrorException;
use PhpJsonRpc\Error\ParseErrorException;
use PhpJsonRpc\Error\InvalidResponseException;

class ResponseParser
{
    /**
     * @param string $payload
     *
     * @return ResultSpecifier
     */
    public function parse(string $payload): ResultSpecifier
    {
        $data = @json_decode($payload, true);

        if (!is_array($data)) {
            throw new ParseErrorException();
        }

        $units = [];

        if ($this->isSingleResponse($data)) {
            if ($this->isValidResult($data)) {
                $units[] = new ResultUnit($data['id'], $data['result']);
            } elseif ($this->isValidError($data)) {
                $units[] = new ResultError($data['id'], new ServerErrorException());
            } else {
                throw new InvalidResponseException();
            }

            return new ResultSpecifier($units, true);
        }

        /** @var array $data */
        foreach ($data as $response) {
            if ($this->isValidResult($response)) {
                $units[] = new ResultUnit($response['id'], $response['result']);
            } elseif ($this->isValidError($response)) {
                $units[] = new ResultError($response['id'], new ServerErrorException($response['error']['message'], $response['error']['code']));
            } else {
                throw new InvalidResponseException();
            }
        }

        return new ResultSpecifier($units, false);
    }

    /**
     * @param array $response
     * @return bool
     */
    private function isSingleResponse(array $response): bool
    {
        return array_keys($response) !== range(0, count($response) - 1);
    }

    /**
     * @param array $payload
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
}
