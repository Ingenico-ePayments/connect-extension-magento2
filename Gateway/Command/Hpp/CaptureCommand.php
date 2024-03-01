<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command\Hpp;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\Worldline\Action\CapturePayment;

class CaptureCommand implements CommandInterface
{
    public function __construct(
        private readonly CapturePayment $capturePayment,
    ) {
    }

    public function execute(array $commandSubject): void
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $payment->setIsTransactionClosed(true);
        $payment->getOrder()->setState(Order::STATE_PROCESSING);
        $payment->getOrder()->setStatus(Order::STATE_PROCESSING);

        $this->capturePayment->process($payment, $commandSubject['amount']);
    }
}
