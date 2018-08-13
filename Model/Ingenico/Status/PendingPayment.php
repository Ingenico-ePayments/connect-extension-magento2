<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\Order\EmailManager;

class PendingPayment implements HandlerInterface
{
    /**
     * @var ConfigInterface
     */
    private $epaymentsConfig;

    /**
     * @var EmailManager
     */
    private $orderEMailManager;

    public function __construct(
        EmailManager $emailManager,
        ConfigInterface $epaymentsConfig
    ) {
        $this->epaymentsConfig = $epaymentsConfig;
        $this->orderEMailManager = $emailManager;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $payment = $order->getPayment();
        $payment->setIsTransactionClosed(false);
        $payment->addTransaction(
            Order\Payment\Transaction::TYPE_AUTH,
            $order,
            false,
            $this->epaymentsConfig->getPaymentStatusInfo(StatusInterface::PENDING_PAYMENT)
        );

        $this->orderEMailManager->process(
            $order,
            $ingenicoStatus->status
        );
    }
}
