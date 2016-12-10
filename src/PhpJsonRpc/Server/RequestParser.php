<?php

namespace PhpJsonRpc\Server;

use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Core\Invoke\AbstractInvoke;
use PhpJsonRpc\Core\Invoke\Invoke;
use PhpJsonRpc\Core\Invoke\Error;
use PhpJsonRpc\Core\Invoke\Notification;
use PhpJsonRpc\Core\InvokeSpec;
use PhpJsonRpc\Error\InvalidRequestException;
use PhpJsonRpc\Error\ParseErrorException;
use PhpJsonRpc\Server\RequestParser\ParserContainer;

/**
 * Request parser
 */
class RequestParser
{
    /**
     * @var Interceptor
     */
    private $preParse;

    /**
     * RequestParser constructor.
     */
    public function __construct()
    {
        $this->preParse = Interceptor::createBase();
    }

    /**
     * Parse request data
     *
     * @param string $data
     *
     * @return InvokeSpec
     */
    public function parse(string $data): InvokeSpec
    {
        $payload = @json_decode($data, true);

        if (!is_array($payload)) {
            return new InvokeSpec([new Error(new ParseErrorException())], true);
        }

        $units = [];

        // Single request
        if ($this->isSingleRequest($payload)) {
            $units[] = $this->decodeCall($payload);
            return new InvokeSpec($units, true);
        }

        // Batch request
        /** @var array $payload */
        foreach ($payload as $record) {
            $units[] = $this->decodeCall($record);
        }

        return new InvokeSpec($units, false);
    }

    /**
     * Get pre-parse chain
     *
     * @return Interceptor
     */
    public function onPreParse(): Interceptor
    {
        return $this->preParse;
    }

    /**
     * @param $record
     *
     * @return AbstractInvoke
     */
    private function decodeCall($record): AbstractInvoke
    {
        $record = $this->preParse($record);

        if ($this->isValidCall($record)) {
            $unit = new Invoke($record['id'], $record['method'], $record['params'] ?? []);
        } elseif ($this->isValidNotification($record)) {
            $unit = new Notification($record['method'], $record['params']);
        } else {
            $unit = new Error(new InvalidRequestException());
        }

        return $unit;
    }

    /**
     * @param array $payload
     *
     * @return bool
     */
    private function isSingleRequest(array $payload): bool
    {
        return array_keys($payload) !== range(0, count($payload) - 1);
    }

    /**
     * @param array $payload
     *
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
     *
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

    /**
     * @param mixed $record
     *
     * @return mixed
     */
    private function preParse($record)
    {
        $container = $this->preParse->handle(new ParserContainer($this, $record));

        if ($container instanceof ParserContainer) {
            return $container->getValue();
        }

        throw new \RuntimeException();
    }
}
