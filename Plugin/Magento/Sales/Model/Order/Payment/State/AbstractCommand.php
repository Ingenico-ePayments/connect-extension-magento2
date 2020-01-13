<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Model\Order\Payment\State;

use Ingenico\Connect\Model\ConfigProvider;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

abstract class AbstractCommand
{
    public const KEY_ORDER_MUST_BE_PENDING = 'order_must_be_pending';
    public const KEY_ORDER_MUST_BE_PENDING_PAYMENT = 'order_must_be_pending_payment';

    /**
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     */
    protected function updateOrderStatus(OrderPaymentInterface $payment, OrderInterface $order): void
    {
        if ($payment->getMethod() !== ConfigProvider::CODE) {
            return;
        }

        if ($payment instanceof Payment) {
            if ($payment->getData(self::KEY_ORDER_MUST_BE_PENDING)) {
                $order->setState(Order::STATE_NEW);
                $order->setStatus('pending');
            }

            if ($payment->getData(self::KEY_ORDER_MUST_BE_PENDING_PAYMENT)) {
                $order->setState(Order::STATE_PENDING_PAYMENT);
                $order->setStatus(Order::STATE_PENDING_PAYMENT);
            }
        }
    }
}
