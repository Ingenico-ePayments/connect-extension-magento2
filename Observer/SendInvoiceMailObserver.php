<?php

namespace Netresearch\Epayments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Netresearch\Epayments\Model\ConfigProvider;

class SendInvoiceMailObserver implements ObserverInterface
{
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * SendInvoiceMailObserver constructor.
     *
     * @param InvoiceSender $invoiceSender
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        InvoiceSender $invoiceSender,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Sends the invoice email once the invoice is properly marked as paid
     *
     * Event: sales_order_invoice_pay
     *
     * @param Observer $event
     */
    public function execute(Observer $event)
    {
        /** @var Invoice $invoice */
        $invoice = $event->getEvent()->getData('invoice');
        $order = $invoice->getOrder();

        if ($order->getPayment()->getMethod() !== ConfigProvider::CODE
            || $invoice->getState() !== Invoice::STATE_PAID
            || $invoice->getEmailSent()) {
            return;
        }
        /** Save order and invoice to make sure the email can access the order and invoice number. */
        $order->addRelatedObject($invoice);
        $this->orderRepository->save($order);
        $this->invoiceSender->send($invoice);
    }
}
