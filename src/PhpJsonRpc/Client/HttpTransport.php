<?php

namespace PhpJsonRpc\Client;

use PhpJsonRpc\Error\ConnectionFailureException;

class HttpTransport extends AbstractTransport
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var array
     */
    private $headers = [
        'User-Agent: PhpJsonRpc client <https://github.com/vaderangry/PhpJsonRpc>',
        'Content-Type: application/json',
        'Accept: application/json',
        'Connection: close',
    ];

    /**
     * HttpEngine constructor.
     *
     * @param string $url     URL of RPC server
     * @param int    $timeout HTTP timeout
     */
    public function __construct(string $url, int $timeout = null)
    {
        parent::__construct();

        $this->url     = $url;
        $this->timeout = $timeout ?? 5;
    }

    /**
     * Send request
     *
     * @param string $request
     *
     * @return string
     */
    public function send(string $request): string
    {
        $stream = fopen(trim($this->url), 'rb', false, $this->buildContext($request));

        if (!is_resource($stream)) {
            throw new ConnectionFailureException('Unable to establish a connection');
        }

        return stream_get_contents($stream);
    }

    /**
     * Add headers to any request
     *
     * @param array $headers
     */
    public function addHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * @param string $payload
     * @return resource
     */
    private function buildContext(string $payload)
    {
        $options = array(
            'http' => array(
                'method'           => 'POST',
                'protocol_version' => 1.1,
                'timeout'          => $this->timeout,
                'max_redirects'    => 2,
                'header'           => implode("\r\n", $this->headers),
                'content'          => $payload,
                'ignore_errors'    => true,
            )
        );
        return stream_context_create($options);
    }
}
