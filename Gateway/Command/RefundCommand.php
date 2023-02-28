<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\Worldline\Action\Refund\CreateRefund;

class RefundCommand implements CommandInterface
{
    public function __construct(
        private readonly CreateRefund $createRefund,
        private readonly ApiErrorHandler $apiErrorHandler
    ) {
    }

    public function execute(array $commandSubject)
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $creditmemo = $payment->getCreditmemo();

        try {
            $this->createRefund->process($creditmemo);
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
