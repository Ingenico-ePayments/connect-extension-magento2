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
        $creditmemo = $payment->getCreditmemo();
        $order = $creditmemo->getOrder();
        $storeId = $creditmemo->getStoreId();

        try {
            $response = $this->worldlineClient->worldlineRefund(
                $payment->getRefundTransactionId(),
                $this->refundRequestBuilder->build($order, (float) $creditmemo->getGrandTotal()),
                null,
                $storeId
            );

            $this->statusResponseManager->set($payment, $response->id, $response);

            $payment->setLastTransId($response->id);
            $payment->setTransactionId($response->id);
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
