<?php

declare(strict_types=1);

namespace Ingenico\Connect\Gateway\Command;

use Ingenico\Connect\Model\Config;
use Magento\Sales\Model\Order\Payment;

class IngenicoVaultAuthorizeCommand extends IngenicoAuthorizeCommand
{
    /**
     * @param mixed[] $commandSubject
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(array $commandSubject)
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $payment->setAdditionalInformation(Config::CLIENT_PAYLOAD_IS_PAYMENT_ACCOUNT_ON_FILE, 1);
        $payment->setAdditionalInformation(Config::PRODUCT_PAYMENT_METHOD_KEY, 'card');

        parent::execute($commandSubject);
    }
}
