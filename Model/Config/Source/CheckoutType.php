<?php
/**
 * See LICENSE.md for license details.
 */
namespace Ingenico\Connect\Model\Config\Source;

use Ingenico\Connect\Model\Config;

/**
 * Class CheckoutType
 *
 * @package Ingenico\Connect\Model\Backend\Config\Source
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
