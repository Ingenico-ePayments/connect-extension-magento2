<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Helper\Data as DataHelper;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
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
     * @var CaptureRequestedFactory
     */
    private $captureRequestedFactory;

    /**
     * @var Config
     */
    private $orderConfig;

    /**
     * Captured constructor.
     *
     * @param StatusResponseManagerInterface $statusResponseManager
     * @param CaptureRequestedFactory $captureRequestedFactory
     */
    public function __construct(
        ManagerInterface $eventManager,
        Config $orderConfig,
        StatusResponseManagerInterface $statusResponseManager,
        CaptureRequestedFactory $captureRequestedFactory
    ) {
        parent::__construct($eventManager);
        $this->captureRequestedFactory = $captureRequestedFactory;
        $this->statusResponseManager = $statusResponseManager;
        $this->orderConfig = $orderConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
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
        $order->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING));
        $payment->registerCaptureNotification(DataHelper::reformatMagentoAmount($amount));

        $this->dispatchEvent($order, $ingenicoStatus);
    }
}
