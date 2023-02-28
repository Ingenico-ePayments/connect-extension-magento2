<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Api\Data;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface SessionInterface
{
    /**
     * @api
     * @return string|null
     */
    public function getAssetUrl();

    /**
     * @api
     * @return string|null
     */
    public function getClientApiUrl();

    /**
     * @api
     * @return string|null
     */
    public function getClientSessionId();

    /**
     * @api
     * @return string|null
     */
    public function getCustomerId();

    // phpcs:disable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @api
     * @return string[]|null
     */
    // phpcs:enable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    public function getInvalidTokens();

    /**
     * @api
     * @return string|null
     */
    public function getRegion();
}
