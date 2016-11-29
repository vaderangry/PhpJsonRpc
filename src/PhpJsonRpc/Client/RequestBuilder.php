<?php

namespace PhpJsonRpc\Client;

use PhpJsonRpc\Core\Call\CallUnit;
use PhpJsonRpc\Core\CallSpecifier;

class RequestBuilder
{
    /**
     * @param CallSpecifier $call
     *
     * @return string
     */
    public function build(CallSpecifier $call): string
    {
        $response = [];
        $units    = $call->getUnits();

        foreach ($units as $unit) {
            /** @var CallUnit $unit */
            $response[] = [
                'jsonrpc' => '2.0',
                'method'  => $unit->getRawMethod(),
                'params'  => $unit->getRawParams(),
                'id'      => $unit->getRawId()
            ];
        }

        if ($call->isSingleCall()) {
            return json_encode($response[0]);
        }

        return json_encode($response);
    }
}
