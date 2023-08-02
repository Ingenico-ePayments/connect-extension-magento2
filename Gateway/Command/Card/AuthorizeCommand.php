<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command\Card;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\Worldline\Action\AuthorizePayment;
use Worldline\Connect\Model\Worldline\Action\CreateHostedCheckout;

class AuthorizeCommand implements CommandInterface
{
    public function __construct(
        private readonly AuthorizePayment $authorizePayment,
        private readonly CreateHostedCheckout $createHostedCheckout
    ) {
    }

    public function execute(array $commandSubject): void
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();

        match ($payment->getMethodInstance()->getConfigData('payment_flow')) {
            Config::CONFIG_INGENICO_CHECKOUT_TYPE_OPTIMIZED_FLOW =>
                $this->authorizePayment->process($payment),
            Config::CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT =>
                $this->createHostedCheckout->process($payment, MethodInterface::ACTION_AUTHORIZE),
        };
    }
}
