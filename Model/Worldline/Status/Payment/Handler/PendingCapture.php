<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Helper\Data;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

class PendingCapture extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_capture';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $worldlineStatus)
    {
        $payment = $order->getPayment();
        $amount = $worldlineStatus->paymentOutput->amountOfMoney->amount;
        $amount = Data::reformatMagentoAmount($amount);

        $payment->setIsTransactionClosed(false);
        $payment->registerAuthorizationNotification($amount);

        $this->addOrderComment($order, $worldlineStatus);

        $this->dispatchEvent($order, $worldlineStatus);
    }
}
