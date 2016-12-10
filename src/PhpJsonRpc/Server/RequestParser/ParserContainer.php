<?php

namespace PhpJsonRpc\Server\RequestParser;

use PhpJsonRpc\Common\Interceptor\AbstractContainer;
use PhpJsonRpc\Server\RequestParser;

class ParserContainer extends AbstractContainer
{
    /**
     * @var RequestParser
     */
    private $parser;

    /**
     * @var mixed
     */
    private $value;

    /**
     * PreParseContainer constructor.
     *
     * @param RequestParser $parser
     * @param mixed         $value
     */
    public function __construct(RequestParser $parser, $value)
    {
        $this->parser = $parser;
        $this->value  = $value;
    }

    /**
     * @return RequestParser
     */
    public function getParser(): RequestParser
    {
        return $this->parser;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
