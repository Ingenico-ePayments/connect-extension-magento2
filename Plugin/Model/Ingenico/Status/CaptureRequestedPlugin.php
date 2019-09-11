<?php

namespace Ingenico\Connect\Plugin\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Ingenico\Connect\Model\Ingenico\GlobalCollect\Status\OrderStatusHelper;
use Ingenico\Connect\Model\Ingenico\Status\CapturedFactory;
use Ingenico\Connect\Model\Ingenico\Status\CaptureRequested;

class CaptureRequestedPlugin
{
    /**
     * @var CapturedFactory
     */
    private $captureFactory;

    /**
     * @var OrderStatusHelper
     */
    private $orderStatusHelper;

    /**
     * CaptureRequestedPlugin constructor.
     *
     * @param CapturedFactory $captureFactory
     * @param OrderStatusHelper $orderStatusHelper
     */
    public function __construct(CapturedFactory $captureFactory, OrderStatusHelper $orderStatusHelper)
    {
        $this->captureFactory = $captureFactory;
        $this->orderStatusHelper = $orderStatusHelper;
    }

    /**
     * Rewires the CAPTURE_REQUESTED status for GC CC to CAPTURED to be able to release goods sooner
     *
     * @param CaptureRequested $subject
     * @param $proceed
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function aroundResolveStatus(
        CaptureRequested $subject,
        $proceed,
        OrderInterface $order,
        AbstractOrderStatus $ingenicoStatus
    ) {
        if ($this->orderStatusHelper->shouldOrderSkipPaymentReview($ingenicoStatus)
            && $order->getCaptureRequestedOverwrite() === null
        ) {
            // set exit condition to prevent infinite loop
            $order->setCaptureRequestedOverwrite(true);
            $capturedHandler = $this->captureFactory->create();
            $capturedHandler->resolveStatus($order, $ingenicoStatus);
            /** @var Invoice $invoice */
            foreach ($order->getInvoiceCollection() as $invoice) {
                if ($invoice->getTransactionId() === $ingenicoStatus->id) {
                    $invoice->setState(Invoice::STATE_OPEN);
                    $order->addRelatedObject($invoice);
                }
            }
        } else {
            $proceed($order, $ingenicoStatus);
        }
    }
}
