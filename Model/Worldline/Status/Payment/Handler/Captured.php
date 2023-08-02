<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManagerInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

/**
 * Class Captured
 *
 * @package Worldline\Connect\Model
 */
class Captured extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'captured';

    /**
     * @var StatusResponseManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusResponseManager;

    /**
     * @var Config
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderConfig;

    /**
     * Captured constructor.
     *
     * @param ManagerInterface $eventManager
     * @param Config $orderConfig
     * @param StatusResponseManagerInterface $statusResponseManager
     */
    public function __construct(
        ManagerInterface $eventManager,
        ConfigInterface $config,
        Config $orderConfig,
        StatusResponseManagerInterface $statusResponseManager
    ) {
        parent::__construct($eventManager, $config);
        $this->statusResponseManager = $statusResponseManager;
        $this->orderConfig = $orderConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $worldlineStatus)
    {
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();

        $captureTransaction = $this->statusResponseManager->getTransactionBy($worldlineStatus->id, $payment);

        if ($order->getState() === Order::STATE_PAYMENT_REVIEW && $order->getStatus() === Order::STATUS_FRAUD) {
            $payment->setIsTransactionApproved(true);
            $payment->update(false);
        }

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionClosed(true);
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING));
        $payment->registerCaptureNotification($order->getBaseGrandTotal());

        if ($captureTransaction === null) {
            $payment->setNotificationResult(true);
        }

        $this->dispatchEvent($order, $worldlineStatus);
    }
}
