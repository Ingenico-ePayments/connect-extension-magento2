<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\StatusResponseManagerInterface;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\RequestBuilder\Refund\RefundRequestBuilder;

class RefundCommand implements CommandInterface
{
    public function __construct(
        private readonly RefundRequestBuilder $refundRequestBuilder,
        private readonly ClientInterface $worldlineClient,
        private readonly ApiErrorHandler $apiErrorHandler,
        private readonly StatusResponseManagerInterface $statusResponseManager
    ) {
    }

    public function execute(array $commandSubject)
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $worldlinePaymentId = $payment->getRefundTransactionId();

        try {
            $response = $this->worldlineClient->worldlinePayment($worldlinePaymentId);
            if ($response->statusOutput->isRefundable) {
                $this->refundPayment($worldlinePaymentId, $payment);
            } elseif ($response->statusOutput->isCancellable) {
                $this->cancelPayment($worldlinePaymentId, $payment);
            }
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }

    private function refundPayment(string $worldlinePaymentId, Payment $payment): void
    {
        $creditmemo = $payment->getCreditmemo();
        $response = $this->worldlineClient->worldlineRefund(
            $worldlinePaymentId,
            $this->refundRequestBuilder->build($creditmemo->getOrder(), (float) $creditmemo->getGrandTotal()),
            null,
            $creditmemo->getStoreId()
        );

        $this->statusResponseManager->set($payment, $response->id, $response);

        $payment->setLastTransId($response->id);
        $payment->setTransactionId($response->id);
    }

    private function cancelPayment(string $worldlinePaymentId, Payment $payment): void
    {
        $this->worldlineClient->worldlinePaymentCancel($worldlinePaymentId);

        $response = $this->worldlineClient->worldlinePayment($worldlinePaymentId);

        $this->statusResponseManager->set($payment, $response->id, $response);

        $payment->setLastTransId($response->id);
        $payment->setTransactionId($response->id);
    }
}
