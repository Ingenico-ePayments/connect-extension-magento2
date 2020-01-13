<?php

namespace Ingenico\Connect\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Model\StatusResponseManager;

class Paid implements HandlerInterface
{
    /**
     * @var CapturedFactory
     */
    private $capturedFactory;

    /**
     * @var ConfigInterface
     */
    private $ePaymentsConfig;

    /**
     * @var StatusResponseManager
     */
    private $statusResponseManager;

    /**
     * Paid constructor.
     *
     * @param CapturedFactory $capturedFactory
     * @param ConfigInterface $ePaymentsConfig
     * @param StatusResponseManager $statusResponseManager
     */
    public function __construct(
        CapturedFactory $capturedFactory,
        ConfigInterface $ePaymentsConfig,
        StatusResponseManager $statusResponseManager
    ) {
        $this->capturedFactory = $capturedFactory;
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->statusResponseManager = $statusResponseManager;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $currentPaymentStatus = '';
        $captureTransaction = $this->statusResponseManager->getTransactionBy($ingenicoStatus->id, $payment);
        if ($captureTransaction !== null) {
            $currentCaptureStatus = $this->statusResponseManager->get($payment, $ingenicoStatus->id);
            $currentPaymentStatus = $currentCaptureStatus->status;
        }

        if ($currentPaymentStatus !== StatusInterface::CAPTURED) {
            /** @var Captured $capturedHandler */
            $capturedHandler = $this->capturedFactory->create();
            $capturedHandler->resolveStatus($order, $ingenicoStatus);
        }

        foreach ($order->getInvoiceCollection() as $invoice) {
            /**
             * Check if an invoice with the same transaction id exists
             */
            if ($invoice->getTransactionId() === $ingenicoStatus->id
                && $invoice->getState() !== Order\Invoice::STATE_PAID
            ) {

                /**
                 * Let the payment update the invoice state and the order totals by performing an arbitrary update
                 * operation
                 */
                $payment->setTransactionId($ingenicoStatus->id);
                $payment->setIsTransactionApproved(true);
                $payment->update(false);
            }
        }

        $order->addCommentToStatusHistory($this->ePaymentsConfig->getPaymentStatusInfo(StatusInterface::PAID));
    }
}
