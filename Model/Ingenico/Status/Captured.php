<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Capture\CaptureResponseFactory;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Helper\Data as DataHelper;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\Order\EmailManager;
use Netresearch\Epayments\Model\StatusResponseManagerInterface;

class Captured implements HandlerInterface
{
    /**
     * @var StatusResponseManagerInterface
     */
    private $statusResponseManager;

    /**
     * @var EmailManager
     */
    private $orderEMailManager;
    /**
     * @var CaptureRequestedFactory
     */
    private $captureRequestedFactory;

    /**
     * @var CaptureResponseFactory
     */
    private $captureResponseFactory;

    /**
     * Captured constructor.
     * @param StatusResponseManagerInterface $statusResponseManager
     * @param CaptureRequestedFactory $captureRequestedFactory
     * @param EmailManager $emailManager
     */
    public function __construct(
        StatusResponseManagerInterface $statusResponseManager,
        CaptureRequestedFactory $captureRequestedFactory,
        EmailManager $emailManager
    ) {
        $this->captureRequestedFactory = $captureRequestedFactory;
        $this->statusResponseManager = $statusResponseManager;
        $this->orderEMailManager = $emailManager;
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

        $captureTransaction = $this->statusResponseManager->getTransactionBy($ingenicoStatus->id);

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

        $payment->setNotificationResult(true);
        $payment->setIsTransactionClosed(true);
        $payment->setIsTransactionPending(false);
        $payment->registerCaptureNotification(DataHelper::reformatMagentoAmount($amount));

        $this->orderEMailManager->process($order, $ingenicoStatus->status);
    }
}
