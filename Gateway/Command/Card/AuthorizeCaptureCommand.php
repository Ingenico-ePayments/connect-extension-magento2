<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command\Card;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\Worldline\Action\AuthorizeCapturePayment;
use Worldline\Connect\Model\Worldline\Action\CreateHostedCheckout;

class AuthorizeCaptureCommand implements CommandInterface
{
    public function __construct(
        private readonly AuthorizeCapturePayment $authorizeCapturePayment,
        private readonly CreateHostedCheckout $createHostedCheckout
    ) {
    }

    public function execute(array $commandSubject): void
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $payment->setIsTransactionClosed(true);
        $payment->getOrder()->setState(Order::STATE_PROCESSING);
        $payment->getOrder()->setStatus(Order::STATE_PROCESSING);

        match ($payment->getMethodInstance()->getConfigData('payment_flow')) {
            Config::CONFIG_INGENICO_CHECKOUT_TYPE_OPTIMIZED_FLOW =>
                $this->authorizeCapturePayment->process($payment),
            Config::CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT =>
                $this->createHostedCheckout->process($payment, MethodInterface::ACTION_AUTHORIZE_CAPTURE),
        };
    }
}
