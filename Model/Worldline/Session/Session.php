<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Session;

use Ingenico\Connect\Sdk\Domain\Sessions\SessionResponse;
use Worldline\Connect\Api\Data\SessionInterface;

class Session extends SessionResponse implements SessionInterface
{
    /**
     * @return string|null
     * @api
     */
    public function getAssetUrl()
    {
        return $this->assetUrl;
    }

    /**
     * @return string|null
     * @api
     */
    public function getClientApiUrl()
    {
        return $this->clientApiUrl;
    }

    /**
     * @return string|null
     * @api
     */
    public function getClientSessionId()
    {
        return $this->clientSessionId;
    }

    /**
     * @return string|null
     * @api
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    // phpcs:disable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @return string[]
     * @api
     */
    // phpcs:enable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    public function getInvalidTokens()
    {
        return $this->invalidTokens;
    }

    /**
     * @return string|null
     * @api
     */
    public function getRegion()
    {
        return $this->region;
    }
}
