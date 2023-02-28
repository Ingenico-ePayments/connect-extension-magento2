<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;

class PendingPayment extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_payment';

    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $epaymentsConfig;

    public function __construct(
        ConfigInterface $epaymentsConfig,
        ManagerInterface $eventManager
    ) {
        parent::__construct($eventManager, $epaymentsConfig);
        $this->epaymentsConfig = $epaymentsConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $worldlineStatus)
    {
        $payment = $order->getPayment();
        $payment->setIsTransactionClosed(false);
        $payment->addTransaction(
            Order\Payment\Transaction::TYPE_AUTH,
            $order,
            false,
            $this->epaymentsConfig->getPaymentStatusInfo(StatusInterface::PENDING_PAYMENT)
        );

        $this->addOrderComment($order, $worldlineStatus);

        $this->dispatchEvent($order, $worldlineStatus);
    }
}
