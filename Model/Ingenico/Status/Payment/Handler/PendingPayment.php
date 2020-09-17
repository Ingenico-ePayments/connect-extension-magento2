<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;

class PendingPayment extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_payment';

    /**
     * @var ConfigInterface
     */
    private $epaymentsConfig;

    public function __construct(
        ConfigInterface $epaymentsConfig,
        ManagerInterface $eventManager
    ) {
        parent::__construct($eventManager);
        $this->epaymentsConfig = $epaymentsConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        $payment = $order->getPayment();
        $payment->setIsTransactionClosed(false);
        $payment->addTransaction(
            Order\Payment\Transaction::TYPE_AUTH,
            $order,
            false,
            $this->epaymentsConfig->getPaymentStatusInfo(StatusInterface::PENDING_PAYMENT)
        );

        $this->dispatchEvent($order, $ingenicoStatus);
    }
}
