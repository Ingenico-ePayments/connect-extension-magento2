<?php

namespace Ingenico\Connect\Model\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\Data\OrderInterface;

interface ServiceInterface
{
    /**
     * Fills quote/cart from order items
     *
     * @param CheckoutSession $session
     * @param OrderInterface $order
     * @return bool - true if no errors occured, otherwise false
     */
    public function fillCartFromOrder(CheckoutSession $session, OrderInterface $order);

    /**
     * Returns errors that occured during process
     *
     * @return string[]
     */
    public function getErrors();
}
