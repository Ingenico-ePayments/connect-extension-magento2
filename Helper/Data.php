<?php

namespace Netresearch\Epayments\Helper;

abstract class Data
{
    /**
     * Format amount for Ingenico Connect API
     *
     * @param float $amount
     * @return int
     */
    public static function formatIngenicoAmount($amount)
    {
        return (int)number_format($amount * 100, 0, '.', '');
    }

    /**
     * Reverse Ingenico formatting for money amount
     *
     * @param int $amount
     * @return float|int
     */
    public static function reformatMagentoAmount($amount)
    {
        return $amount / 100;
    }
}
