<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Helper;

abstract class Data
{
    /**
     * Format amount for Worldline Connect API
     *
     * @param float $amount
     * @return int
     */
    public static function formatWorldlineAmount($amount): int
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return (int) number_format($amount * 100, 0, '.', '');
    }

    /**
     * Reverse Worldline formatting for money amount
     *
     * @param int $amount
     * @return float
     */
    public static function reformatMagentoAmount($amount): float
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return floatval($amount / 100);
    }
}
