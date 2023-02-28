<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\CreateSession;

use Ingenico\Connect\Sdk\Domain\Sessions\SessionRequest;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionRequestFactory;

class RequestBuilder
{
    /** @var SessionRequestFactory */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $sessionRequestFactory;

    /**
     * @param SessionRequestFactory $sessionRequestFactory
     */
    public function __construct(SessionRequestFactory $sessionRequestFactory)
    {
        $this->sessionRequestFactory = $sessionRequestFactory;
    }

    /**
     * @param array $tokens
     * @return SessionRequest
     */
    public function build(array $tokens = [])
    {
        $sessionRequest = $this->sessionRequestFactory->create();
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (count($tokens)) {
            $sessionRequest->tokens = $tokens;
        }

        return $sessionRequest;
    }
}
