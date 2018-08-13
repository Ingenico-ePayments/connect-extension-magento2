<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Helper\Data;
use Netresearch\Epayments\Model\Order\EmailManager;

class PendingApproval implements HandlerInterface
{

    /**
     * @var EmailManager
     */
    private $orderEMailManager;

    /**
     * PendingApproval constructor.
     * @param EmailManager $orderEMailManager
     */
    public function __construct(EmailManager $orderEMailManager)
    {
        $this->orderEMailManager = $orderEMailManager;
    }

    /**
     * @param OrderInterface $order
     * @param AbstractOrderStatus $ingenicoStatus
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        /** @var OrderPaymentInterface|Order\Payment $payment */
        $payment = $order->getPayment();
        $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        $amount = Data::reformatMagentoAmount($amount);

        $payment->setIsTransactionClosed(false);
        $payment->registerAuthorizationNotification($amount);

        $this->orderEMailManager->process($order, $ingenicoStatus->status);
    }
}
