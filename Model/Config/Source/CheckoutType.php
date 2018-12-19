<?php
/**
 * See LICENSE.md for license details.
 */
namespace Netresearch\Epayments\Model\Config\Source;

use Netresearch\Epayments\Model\Config;

/**
 * Class CheckoutType
 *
 * @package Netresearch\Epayments\Model\Backend\Config\Source
 * @author Max Melzer <max.melzer@netresearch.de>
 * @copyright 2018 Netresearch GmbH & Co. KG
 * @link http://www.netresearch.de/
 */
class CheckoutType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT,
                'label' => __('Payment products and input fields on Hosted Checkout')
            ],
            [
                'value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT,
                'label' => __('Payment products in Magento checkout, input fields on Hosted Checkout')
            ],
            [
                'value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_INLINE,
                'label' => __('Payment products and input fields in Magento checkout (inline)')
            ],
        ];
    }
}
