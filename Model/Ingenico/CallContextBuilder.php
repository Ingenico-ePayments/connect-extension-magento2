<?php

namespace Netresearch\Epayments\Model\Ingenico;

use Ingenico\Connect\Sdk\CallContext;

class CallContextBuilder
{
    /**
     * @var CallContext
     */
    private $callContext;

    /**
     * @var string
     */
    private $idempotenceKey = null;

    /**
     * CallContextBuilder constructor.
     *
     * @param CallContext $callContext
     */
    public function __construct(CallContext $callContext)
    {
        $this->callContext = $callContext;
    }

    public function create()
    {
        $this->callContext->setIdempotenceKey($this->idempotenceKey);

        return $this->callContext;
    }

    /**
     * @param string $idempotenceKey
     */
    public function setIdempotenceKey($idempotenceKey)
    {
        $this->idempotenceKey = $idempotenceKey;
    }
}
