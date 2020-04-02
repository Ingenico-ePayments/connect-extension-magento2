<?php

namespace Ingenico\Connect\Helper;

abstract class Data
{
    /**
     * Format amount for Ingenico Connect API
     *
     * @param float $amount
     * @return int
     */
    public static function formatIngenicoAmount($amount): int
    {
        return (int) number_format($amount * 100, 0, '.', '');
    }

    /**
     * Reverse Ingenico formatting for money amount
     *
     * @param int $amount
     * @return float
     */
    public static function reformatMagentoAmount($amount): float
    {
        return floatval($amount / 100);
    }
}
