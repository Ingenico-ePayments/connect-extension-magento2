<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline;

use Ingenico\Connect\Sdk\CallContext;

class CallContextBuilder
{
    /**
     * @var CallContext
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $callContext;

    /**
     * @var string
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
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
