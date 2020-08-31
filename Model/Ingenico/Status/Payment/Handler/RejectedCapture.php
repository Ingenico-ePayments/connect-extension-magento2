<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;

class RejectedCapture extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'rejected_capture';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        // @todo: Remove dependency on OrderInterface implementation:
        if ($order instanceof Order) {
            $invoice = $this->getInvoiceForTransaction($order, $ingenicoStatus->id);
            if ($invoice) {
                $invoice->cancel();
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
