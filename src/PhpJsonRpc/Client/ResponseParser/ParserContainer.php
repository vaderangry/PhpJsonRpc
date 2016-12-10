<?php

namespace PhpJsonRpc\Client\ResponseParser;

use PhpJsonRpc\Client\ResponseParser;
use PhpJsonRpc\Common\Interceptor\AbstractContainer;

class ParserContainer extends AbstractContainer
{
    /**
     * @var ResponseParser
     */
    private $parser;

    /**
     * @var array
     */
    private $value;

    /**
     * ParseContainer constructor.
     *
     * @param ResponseParser $parser
     * @param array          $value
     */
    public function __construct(ResponseParser $parser, array $value)
    {
        $this->parser = $parser;
        $this->value  = $value;
    }

    /**
     * @return ResponseParser
     */
    public function getParser(): ResponseParser
    {
        return $this->parser;
    }

    /**
     * @return array
     */
    public function getValue(): array
    {
        return $this->value;
    }
}
