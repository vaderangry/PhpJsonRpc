<?php

namespace PhpJsonRpc\Client;

use PhpJsonRpc\Client\RequestBuilder\BuilderContainer;
use PhpJsonRpc\Common\Interceptor\Interceptor;
use PhpJsonRpc\Core\Invoke\AbstractInvoke;
use PhpJsonRpc\Core\Invoke\Invoke;
use PhpJsonRpc\Core\InvokeSpec;

class RequestBuilder
{
    /**
     * @var Interceptor
     */
    private $preBuild;

    /**
     * RequestBuilder constructor.
     */
    public function __construct()
    {
        $this->preBuild = Interceptor::createBase();
    }

    /**
     * @param InvokeSpec $call
     *
     * @return string
     */
    public function build(InvokeSpec $call): string
    {
        $response = [];
        $units    = $call->getUnits();

        foreach ($units as $invoke) {
            /** @var Invoke $invoke */
            $invoke = $this->preBuild($invoke);

            $response[] = [
                'jsonrpc' => '2.0',
                'method'  => $invoke->getRawMethod(),
                'params'  => $invoke->getRawParams(),
                'id'      => $invoke->getRawId()
            ];
        }

        if ($call->isSingleCall()) {
            return json_encode($response[0]);
        }

        return json_encode($response);
    }

    /**
     * @return Interceptor
     */
    public function onPreBuild(): Interceptor
    {
        return $this->preBuild;
    }

    /**
     * @param AbstractInvoke $invoke
     *
     * @return AbstractInvoke
     */
    private function preBuild(AbstractInvoke $invoke): AbstractInvoke
    {
        $result = $this->preBuild->handle(new BuilderContainer($this, $invoke));

        if ($result instanceof BuilderContainer) {
            return $result->getInvoke();
        }

        throw new \RuntimeException();
    }
}
