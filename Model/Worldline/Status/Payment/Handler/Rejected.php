<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Model\Order;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

class Rejected extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'rejected';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Order $order, Payment $status)
    {
        /** @var Order\Payment $orderPayment */
        $orderPayment = $order->getPayment();
        $orderPayment->setIsTransactionClosed(true);
        $orderPayment->setIsTransactionDenied(true);
        $orderPayment->update();

        $this->dispatchEvent($order, $status);
    }
}
