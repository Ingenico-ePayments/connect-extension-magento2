<?php

namespace Ingenico\Connect\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class Cancelled implements HandlerInterface
{
    /**
     * @param OrderInterface|Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoOrderStatus)
    {
        $order->registerCancellation(
            "Canceled Order with status {$ingenicoOrderStatus->status}"
        );
    }
}
