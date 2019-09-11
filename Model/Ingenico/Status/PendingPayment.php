<?php

namespace Ingenico\Connect\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;

class PendingPayment implements HandlerInterface
{
    /**
     * @var ConfigInterface
     */
    private $epaymentsConfig;

    public function __construct(
        ConfigInterface $epaymentsConfig
    ) {
        $this->epaymentsConfig = $epaymentsConfig;
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
    }
}
