<?php

namespace Vaderangry\PhpJsonRpc\Server;

use Vaderangry\PhpJsonRpc\Core\Call\CallUnit;
use Vaderangry\PhpJsonRpc\Core\Call\CallError;
use Vaderangry\PhpJsonRpc\Core\Call\CallNotification;
use Vaderangry\PhpJsonRpc\Core\CallSpecifier;
use Vaderangry\PhpJsonRpc\Error\InvalidRequestException;
use Vaderangry\PhpJsonRpc\Error\ParseErrorException;

/**
 * Request parser
 */
class RequestParser
{
    /**
     * Parse request data
     *
     * @param string $data
     * @return CallSpecifier
     * @throws ParseErrorException
     */
    public function parse(string $data): CallSpecifier
    {
        $payload = @json_decode($data, true);

        if (!is_array($payload)) {
            return new CallSpecifier([new CallError(new ParseErrorException())], true);
        }

        $units = [];

        // Single request
        if ($this->isSingleRequest($payload)) {
            if ($this->isValidCall($payload)) {
                $units[] = new CallUnit($payload['id'], $payload['method'], $payload['params'] ?? []);
            } elseif ($this->isValidNotification($payload)) {
                $units[] = new CallNotification($payload['method'], $payload['params']);
            } else {
                $units[] = new CallError(new InvalidRequestException());
            }

            return new CallSpecifier($units, true);
        }

        // Batch request
        foreach ($payload as $record) {
            if ($this->isValidCall($record)) {
                $units[] = new CallUnit($record['id'], $record['method'], $record['params'] ?? []);
            } elseif ($this->isValidNotification($record)) {
                $units[] = new CallNotification($record['method'], $record['params'] ?? []);
            } else {
                $units[] = new CallError(new InvalidRequestException());
            }
        }

        return new CallSpecifier($units, false);
    }

    /**
     * @param array $payload
     * @return bool
     */
    private function isSingleRequest(array $payload): bool
    {
        return array_keys($payload) !== range(0, count($payload) - 1);
    }

    /**
     * @param array $payload
     * @return bool
     */
    private function isValidCall($payload): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        $headerValid = array_key_exists('jsonrpc', $payload) && $payload['jsonrpc'] === '2.0';
        $methodValid = array_key_exists('method', $payload)  && is_string($payload['method']);
        $idValid     = array_key_exists('id', $payload);

        // This member MAY be omitted
        $paramsValid = true;
        if (array_key_exists('params', $payload) && !is_array($payload['params'])) {
            $paramsValid = false;
        }

        return $headerValid && $methodValid && $paramsValid && $idValid;
    }

    /**
     * @param array $payload
     * @return bool
     */
    private function isValidNotification($payload): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        $headerValid = array_key_exists('jsonrpc', $payload) && $payload['jsonrpc'] === '2.0';
        $methodValid = array_key_exists('method', $payload)  && is_string($payload['method']);
        $idValid     = !array_key_exists('id', $payload);

        // This member MAY be omitted
        $paramsValid = true;
        if (array_key_exists('params', $payload) && !is_array($payload['params'])) {
            $paramsValid = false;
        }

        return $headerValid && $methodValid && $paramsValid && $idValid;
    }
}
