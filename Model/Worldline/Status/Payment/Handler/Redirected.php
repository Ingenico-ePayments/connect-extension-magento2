<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

class Redirected extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'redirected';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Order $order, Payment $status)
    {
        /** @var OrderPayment $orderPayment */
        $orderPayment = $order->getPayment();
        $orderPayment->setIsTransactionClosed(false);

        $this->dispatchEvent($order, $status);
    }
}
