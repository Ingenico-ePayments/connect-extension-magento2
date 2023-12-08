<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Worldline\Connect\Gateway\Command\CreatePaymentRequest\CardRequestBuilder;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Token\TokenService;

class AuthorizeCapturePayment extends AbstractAction
{
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        private readonly CardRequestBuilder $cardRequestBuilder,
        private readonly TokenService $tokenService,
        private readonly MerchantAction $merchantAction
    ) {
        parent::__construct(
            $statusResponseManager,
            $worldlineClient,
            $transactionManager,
            $config
        );
    }

    public function process(OrderPayment $payment): Payment
    {
        $request = $this->cardRequestBuilder->build($payment, MethodInterface::ACTION_AUTHORIZE_CAPTURE);
        $response = $this->worldlineClient->createPayment($request);

        $this->postProcess($payment, $response->payment);

        $this->tokenService->createByOrderAndPayment($payment->getOrder(), $response->payment);
        $this->merchantAction->handle($payment, $response);

        return $response->payment;
    }
}
