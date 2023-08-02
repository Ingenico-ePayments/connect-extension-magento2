<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action;

use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Gateway\Command\CreatePaymentRequest\CardRequestBuilder;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;
use Worldline\Connect\Model\Worldline\Token\TokenService;

class AuthorizePayment extends AbstractAction
{
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        private readonly CardRequestBuilder $cardRequestBuilder,
        private readonly TokenService $tokenService,
    ) {
        parent::__construct(
            $statusResponseManager,
            $worldlineClient,
            $transactionManager,
            $config
        );
    }

    public function process(Payment $payment): void
    {
        $request = $this->cardRequestBuilder->build($payment, MethodInterface::ACTION_AUTHORIZE);
        $response = $this->worldlineClient->createPayment($request);

        $this->postProcess($payment, $response->payment);

        $this->tokenService->createByOrderAndPayment($payment->getOrder(), $response->payment);

        match ($response->payment->status) {
            StatusInterface::PENDING_APPROVAL => $this->paymentPendingApproval($payment),
            StatusInterface::PENDING_FRAUD_APPROVAL => $this->paymentPendingFraudApproval($payment),
            StatusInterface::CAPTURE_REQUESTED => $this->paymentCaptureRequested($payment),
        };
    }

    private function paymentPendingApproval(Payment $payment): void
    {
        $payment->setIsTransactionClosed(false);
    }

    private function paymentPendingFraudApproval(Payment $payment): void
    {
        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(true);
        $payment->setIsFraudDetected(true);
    }

    private function paymentCaptureRequested(Payment $payment): void
    {
        $payment->setIsTransactionClosed(true);
    }
}
