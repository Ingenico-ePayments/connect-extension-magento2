<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Helper\Data;
use Netresearch\Epayments\Model\Order\EmailManagerFraud;

class PendingFraudApproval implements HandlerInterface
{
    /**
     * @var EmailManagerFraud
     */
    private $fraudEmailManager;

    /**
     * PendingFraudApproval constructor.
     *
     * @param EmailManagerFraud $fraudEmailManager
     */
    public function __construct(
        EmailManagerFraud $fraudEmailManager
    ) {
        $this->fraudEmailManager = $fraudEmailManager;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        $amount = Data::reformatMagentoAmount($amount);

        /** @var Payment $payment */
        $payment = $order->getPayment();

        $payment->setIsFraudDetected(true);
        $payment->setIsTransactionClosed(false);
        $payment->registerAuthorizationNotification($amount);

        $this->fraudEmailManager->process($order);
    }
}
