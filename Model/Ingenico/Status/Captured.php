<?php

namespace Ingenico\Connect\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Helper\Data as DataHelper;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Model\StatusResponseManagerInterface;

/**
 * Class Captured
 *
 * @package Ingenico\Connect\Model
 */
class Captured implements HandlerInterface
{
    /**
     * @var StatusResponseManagerInterface
     */
    private $statusResponseManager;

    /**
     * @var CaptureRequestedFactory
     */
    private $captureRequestedFactory;

    /**
     * Captured constructor.
     *
     * @param StatusResponseManagerInterface $statusResponseManager
     * @param CaptureRequestedFactory $captureRequestedFactory
     */
    public function __construct(
        StatusResponseManagerInterface $statusResponseManager,
        CaptureRequestedFactory $captureRequestedFactory
    ) {
        $this->captureRequestedFactory = $captureRequestedFactory;
        $this->statusResponseManager = $statusResponseManager;
    }

    /**
     * @param OrderInterface $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws LocalizedException
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        $captureTransaction = $this->statusResponseManager->getTransactionBy($ingenicoStatus->id, $payment);

        if ($captureTransaction !== null) {
            $currentCaptureStatus = $this->statusResponseManager->get($payment, $ingenicoStatus->id);
            $currentIngenicoPaymentStatus = $currentCaptureStatus->status;

            if ($currentIngenicoPaymentStatus !== StatusInterface::CAPTURE_REQUESTED) {
                /** @var CaptureRequested $captureRequestedHandler */
                $captureRequestedHandler = $this->captureRequestedFactory->create();
                $captureRequestedHandler->resolveStatus($order, $ingenicoStatus);
            }
        }

        if ($ingenicoStatus instanceof \Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment) {
            $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        } elseif ($ingenicoStatus instanceof \Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture) {
            $amount = $ingenicoStatus->captureOutput->amountOfMoney->amount;
        } else {
            throw new LocalizedException(__('Unknown order status.'));
        }

        if ($order->getState() === Order::STATE_PAYMENT_REVIEW && $order->getStatus() === Order::STATUS_FRAUD) {
            $payment->setIsTransactionApproved(true);
            $payment->update(false);
        }
        $payment->setNotificationResult(true);
        $payment->setIsTransactionClosed(true);
        $payment->setIsTransactionPending(false);
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
        $payment->registerCaptureNotification(DataHelper::reformatMagentoAmount($amount));
    }
}
