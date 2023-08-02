<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;

use function in_array;

class FetchTransactionInfoCommand implements CommandInterface
{
    public function __construct(
        private readonly ClientInterface $worldlineClient
    ) {
    }

    public function execute(array $commandSubject): void
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();

        $worldlinePayment = $this->worldlineClient->worldlinePayment(
            $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY)
        );

        if (in_array($worldlinePayment->status, StatusInterface::APPROVED_STATUSES, true)) {
            $payment->registerCaptureNotification($payment->getOrder()->getBaseGrandTotal());
            $payment->setIsTransactionApproved(true);
        }

        if (in_array($worldlinePayment->status, StatusInterface::DENIED_STATUSES, true)) {
            $payment->setIsTransactionDenied(true);
        }
    }
}
