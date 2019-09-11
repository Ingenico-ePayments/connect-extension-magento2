<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Sales\Operation;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Operations\CaptureOperation;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\StatusInterface;

class EnforcePendingInvoice
{
    /**
     * Prevents the invoice being set to paid if the operation on Ingenico side is actually not yet finished
     *
     * @param CaptureOperation $subject
     * @param OrderPaymentInterface|Payment $result
     * @param OrderPaymentInterface|Payment $payment
     * @param Invoice|null $invoice
     * @return OrderPaymentInterface
     */
    public function afterCapture(CaptureOperation $subject, OrderPaymentInterface $result, $payment, $invoice)
    {
        if ($invoice === null) {
            /** @var Invoice $invoice */
            $invoice = $result->getCreatedInvoice();
        }

        if ($result->getAdditionalInformation(Config::PAYMENT_STATUS_KEY) === StatusInterface::CAPTURE_REQUESTED
            && $result->getAdditionalInformation(Config::PRODUCT_PAYMENT_METHOD_KEY) === 'card'
        ) {
            /**
             * Prevent pay operation on invoice later in the process, since the invoice is actually not yet paid on
             * Ingenico side.
             * This will also prevent update of *_amount_paid order totals, which needs to be taken care of in a status
             * handler later, see Ingenico\Connect\Model\Ingenico\Status\Paid::resolveStatus
             */
            $invoice->setIsPaid(false);
            // Set invoice to pending
            $invoice->setState(Invoice::STATE_OPEN);
        }

        return $result;
    }
}
