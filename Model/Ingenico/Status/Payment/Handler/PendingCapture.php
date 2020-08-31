<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Helper\Data;

class PendingCapture extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_capture';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        $payment = $order->getPayment();
        $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        $amount = Data::reformatMagentoAmount($amount);

        $payment->setIsTransactionClosed(false);
        $payment->registerAuthorizationNotification($amount);

        $this->dispatchEvent($order, $ingenicoStatus);
    }
}
