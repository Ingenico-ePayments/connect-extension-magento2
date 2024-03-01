<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

class PendingPayment extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_payment';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Order $order, Payment $status)
    {
        /** @var OrderPayment $orderPayment */
        $orderPayment = $order->getPayment();
        $orderPayment->setIsTransactionClosed(false);
        $orderPayment->addTransaction(TransactionInterface::TYPE_AUTH, $order);

        $this->dispatchEvent($order, $status);
    }
}
