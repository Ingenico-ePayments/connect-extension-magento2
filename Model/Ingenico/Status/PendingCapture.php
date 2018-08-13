<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Helper\Data;
use Netresearch\Epayments\Model\Order\EmailManager;

class PendingCapture implements HandlerInterface
{
    /**
     * @var EmailManager
     */
    private $orderEMailManager;

    /**
     * PendingCapture constructor.
     * @param EmailManager $orderEMailManager
     */
    public function __construct(EmailManager $orderEMailManager)
    {
        $this->orderEMailManager = $orderEMailManager;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus|\Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment $ingenicoStatus
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $payment = $order->getPayment();
        $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        $amount = Data::reformatMagentoAmount($amount);

        $payment->setIsTransactionClosed(false);
        $payment->registerAuthorizationNotification($amount);

        $this->orderEMailManager->process($order, $ingenicoStatus->status);
    }
}
