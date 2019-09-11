<?php

namespace Ingenico\Connect\Model\OrderUpdate;

interface OrderInterface
{
    /**
     * Update order status
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function process(\Magento\Sales\Model\Order $order);
}
