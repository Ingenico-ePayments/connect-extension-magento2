<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

class RejectedCapture extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'rejected_capture';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Order $order, Payment $status)
    {
        $invoice = $this->getInvoiceForTransaction($order, $status->id);
        if ($invoice) {
            $invoice->cancel();
        }

        $this->dispatchEvent($order, $status);
    }

    /**
     * Return invoice model for transaction
     *
     * @param Order $order
     * @param string $transactionId
     * @return Invoice|null
     */
    private function getInvoiceForTransaction(Order $order, string $transactionId)
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $invoices = array_filter(
            $order->getInvoiceCollection()->getItems(),
            // phpcs:ignore SlevomatCodingStandard.Functions.StaticClosure.ClosureNotStatic
            function ($invoice) use ($transactionId) {
                /** @var Invoice $invoice */
                return $invoice->getTransactionId() === $transactionId;
            }
        );
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return array_shift($invoices);
    }
}
