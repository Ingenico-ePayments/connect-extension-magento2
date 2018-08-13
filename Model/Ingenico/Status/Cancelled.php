<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Cancelled implements HandlerInterface
{
    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * Cancelled constructor.
     * @param OrderSender $orderSender
     */
    public function __construct(
        OrderSender $orderSender
    ) {
        $this->orderSender = $orderSender;
    }

    /**
     * @param OrderInterface|Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoOrderStatus)
    {
        $order->registerCancellation(
            "Canceled Order with status {$ingenicoOrderStatus->status}"
        );
        $this->orderSender->send($order);
    }
}
