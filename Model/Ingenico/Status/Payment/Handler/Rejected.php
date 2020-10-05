<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;

class Rejected extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'rejected';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        // @todo: don't depend on OrderInterface implementation:
        if ($order instanceof Order) {
            // Also cancel the invoice, otherwise we cannot cancel the order:
            $invoice = $this->getInvoiceForTransaction($order, $ingenicoStatus->id);
            if ($invoice) {
                $invoice->cancel();
            }
            $order->registerCancellation(
                "Order was canceled with status {$ingenicoStatus->status}"
            );
            if ($invoice) {
                $order->addRelatedObject($invoice);
            }
        }

        $this->dispatchEvent($order, $ingenicoStatus);
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
        $invoices = array_filter(
            $order->getInvoiceCollection()->getItems(),
            function ($invoice) use ($transactionId) {
                /** @var Invoice $invoice */
                return $invoice->getTransactionId() === $transactionId;
            }
        );
        return array_shift($invoices);
    }
}
