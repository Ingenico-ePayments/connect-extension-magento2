<?php

declare(strict_types=1);

namespace Worldline\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\StatusResolver;

class SalesOrderPaymentPlaceEnd implements ObserverInterface
{
    public function __construct(
        private readonly StatusResolver $statusResolver,
    ) {
    }

    public function execute(Observer $observer)
    {
        /** @var Payment $payment */
        $payment = $observer->getData('payment');
        $order = $payment->getOrder();

        match ($payment->getOrderState()) {
            Order::STATE_PENDING_PAYMENT => $this->setState($order, Order::STATE_PENDING_PAYMENT),
            Order::STATE_CANCELED => $this->setState($order, Order::STATE_CANCELED),
            default => $this->setState($order, null),
        };
    }

    private function setState(Order $order, ?string $state): void
    {
        if ($state === null) {
            return;
        }

        $order->setState($state)->setStatus($this->statusResolver->getOrderStatusByState($order, $state));
    }
}
