<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

class Paid extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'paid';

    /**
     * {@inheritDoc}
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function resolveStatus(Order $order, Payment $status)
    {
        /** @var OrderPayment $orderPayment */
        $orderPayment = $order->getPayment();
        $orderPayment->setIsTransactionPending(false);
        $orderPayment->setIsTransactionClosed(true);

        $orderPayment->registerCaptureNotification($order->getBaseGrandTotal());

        $this->dispatchEvent($order, $status);
    }
}
