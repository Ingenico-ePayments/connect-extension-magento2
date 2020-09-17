<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Helper\Data;
use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture as IngenicoCapture;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\StatusResponseManagerInterface;
use Magento\Sales\Model\Order\Config;

/**
 * Class Captured
 *
 * @package Ingenico\Connect\Model
 */
class Captured extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'captured';

    /**
     * @var StatusResponseManagerInterface
     */
    private $statusResponseManager;

    /**
     * @var Config
     */
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
        Config $orderConfig,
        StatusResponseManagerInterface $statusResponseManager
    ) {
        parent::__construct($eventManager);
        $this->statusResponseManager = $statusResponseManager;
        $this->orderConfig = $orderConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();

        if ($ingenicoStatus instanceof IngenicoPayment) {
            $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        } elseif ($ingenicoStatus instanceof IngenicoCapture) {
            $amount = $ingenicoStatus->captureOutput->amountOfMoney->amount;
        } else {
            throw new LocalizedException(__('Unknown order status.'));
        }

        $captureTransaction = $this->statusResponseManager->getTransactionBy($ingenicoStatus->id, $payment);

        if ($order->getState() === Order::STATE_PAYMENT_REVIEW && $order->getStatus() === Order::STATUS_FRAUD) {
            $payment->setIsTransactionApproved(true);
            $payment->update(false);
        }

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionClosed(true);
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING));
        $payment->registerCaptureNotification(Data::reformatMagentoAmount($amount));

        if ($captureTransaction === null) {
            $payment->setNotificationResult(true);
        }

        $this->dispatchEvent($order, $ingenicoStatus);
    }
}
