<?php
/**
 * See LICENSE.md for license details.
 */

namespace Netresearch\Epayments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\Ingenico\Action\CreatePayment;

class IngenicoAuthorizeCommand implements CommandInterface
{
    /**
     * @var CreatePayment
     */
    private $createPaymentAction;

    /**
     * IngenicoAuthorizeCommand constructor.
     *
     * @param CreatePayment $createPaymentAction
     */
    public function __construct(CreatePayment $createPaymentAction)
    {
        $this->createPaymentAction = $createPaymentAction;
    }

    /**
     * @param mixed[] $commandSubject
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(array $commandSubject)
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $order = $payment->getOrder();

        $this->createPaymentAction->create($order);
        $payment->setAdditionalInformation(Config::CLIENT_PAYLOAD_KEY, null);
    }
}
