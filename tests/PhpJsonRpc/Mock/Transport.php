<?php

namespace PhpJsonRpc\Tests\Mock;

use PhpJsonRpc\Client\AbstractTransport;

class Transport extends AbstractTransport
{
    public function send(string $request): string
    {
        $data = [];

        // Single request
        $data['{"jsonrpc":"2.0","method":"Math.pow","params":[2,3],"id":1}'] = '{"jsonrpc":"2.0","result":8,"id":1}';

        $data['{"jsonrpc":"2.0","method":"Math.pow","params":[2,4],"id":1}'] = '{"jsonrpc":"2.0","result":16,"id":1}';

        $data['{"jsonrpc":"2.0","method":"User.getOne","params":[8],"id":1}'] = '{"jsonrpc":"2.0","result":{"id":8,"email":"vader@angry.mil","name":"vader"},"id":1}';

        // Single request with Parse error
        $data['{"jsonrpc":"2.0","method":"Test.parseError","params":[],"id":1}'] = '{';

        // Single request with Server error
        $data['{"jsonrpc":"2.0","method":"Test.serverError","params":[],"id":1}'] = '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found"},"id":1}';

        // Batch request
        $data['[{"jsonrpc":"2.0","method":"Math.pow","params":[2,3],"id":1},{"jsonrpc":"2.0","method":"User.getName","params":{"id":8},"id":2}]'] = '[{"jsonrpc":"2.0","result":"jaguar","id":2}, {"jsonrpc":"2.0","result":8,"id":1}]';

        // Batch request with invalid response
        $key = '[' .
            '{"jsonrpc":"2.0","method":"Math.pow","params":[1,1],"id":1},' .
            '{"jsonrpc":"2.0","method":"User.unknownMethod","params":[],"id":2},' .
            '{"jsonrpc":"2.0","method":"User.getName","params":{"id":9},"id":3}'.
            ']';
        $value = '{"jsonrpc":"2.0","error":{"code": -32600,"message":"Invalid Request"},"id":null}';
        $data[$key] = $value;

        // Batch request with Server error
        $key = '[' .
            '{"jsonrpc":"2.0","method":"Math.pow","params":[2,9],"id":1},' .
            '{"jsonrpc":"2.0","method":"User.unknownMethod","params":[],"id":2},' .
            '{"jsonrpc":"2.0","method":"User.getName","params":{"id":9},"id":3}'.
            ']';
        $value = '[' .
            '{"jsonrpc":"2.0","result":512,"id":1},' .
            '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found"},"id":2},' .
            '{"jsonrpc":"2.0","result":"leo","id":3}' .
            ']';
        $data[$key] = $value;

        return $data[$request] ?? '';
    }
}
