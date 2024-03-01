<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\DeclinedPaymentException;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Gateway\Command\ApiErrorHandler;
use Worldline\Connect\Gateway\Command\CreatePaymentRequest\CardRequestBuilder;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;
use Worldline\Connect\Model\Worldline\Token\TokenService;

class CreatePayment extends AbstractAction
{
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        private readonly CardRequestBuilder $cardRequestBuilder,
        private readonly TokenService $tokenService,
        private readonly MerchantAction $merchantAction,
        private readonly ApiErrorHandler $apiErrorHandler,
    ) {
        parent::__construct(
            $statusResponseManager,
            $worldlineClient,
            $transactionManager,
            $config
        );
    }

    public function process(Payment $payment, bool $requiresApproval): void
    {
        try {
            $request = $this->cardRequestBuilder->build($payment, $requiresApproval);
            $response = $this->worldlineClient->createPayment($request);
            $this->postProcess($payment, $response->payment);

            $this->tokenService->createByOrderAndPayment($payment->getOrder(), $response->payment);
            $this->merchantAction->handle($payment, $response);

            match ($response->payment->status) {
                StatusInterface::CANCELLED => $this->paymentCanceled($payment),
                StatusInterface::PENDING_APPROVAL => $this->paymentPendingApproval($payment),
                StatusInterface::PENDING_FRAUD_APPROVAL => $this->paymentPendingFraudApproval($payment),
                StatusInterface::CAPTURE_REQUESTED => $this->paymentCaptureRequested($payment),
                StatusInterface::REDIRECTED => $this->paymentRedirected(
                    $payment,
                    $response->merchantAction->redirectData->redirectURL
                ),
            };
        } catch (DeclinedPaymentException $e) {
            $this->paymentCanceled($payment);
            $this->postProcess($payment, $e->getPaymentResult()->payment);
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }

    private function paymentCanceled(Payment $payment): void
    {
        $payment->setOrderState(Order::STATE_CANCELED);

        $payment->setIsTransactionClosed(true);
        $payment->setIsTransactionPending(true);
    }

    private function paymentRedirected(Payment $payment, string $url): void
    {
        $payment->setOrderState(Order::STATE_PENDING_PAYMENT);

        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(true);
        $payment->setAdditionalInformation(Config::REDIRECT_URL_KEY, $url);
    }

    private function paymentPendingApproval(Payment $payment): void
    {
        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(false);
    }

    private function paymentPendingFraudApproval(Payment $payment): void
    {
        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(true);
    }

    private function paymentCaptureRequested(Payment $payment): void
    {
        $payment->setIsTransactionClosed(true);
        $payment->setIsTransactionPending(false);
    }
}
