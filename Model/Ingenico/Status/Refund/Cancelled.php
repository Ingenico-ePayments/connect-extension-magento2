<?php

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Ingenico\Connect\Model\Order\Creditmemo\Service;
use Ingenico\Connect\Model\Transaction\TransactionManager;

class Cancelled implements RefundHandlerInterface
{
    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Service
     */
    private $creditMemoService;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var string[]
     */
    private static $totals = [
        'total_refunded' => 'grand_total',
        'base_total_refunded' => 'base_grand_total',
        'subtotal',
        'base_subtotal',
        'base_tax_refunded' => 'base_tax_amount',
        'tax_refunded' => 'tax_amount',
        'base_hidden_tax_refunded' => 'base_hidden_tax_amount',
        'hidden_tax_refunded' => 'hidden_tax_amount',
        'base_shipping_refunded' => 'base_shipping_amount',
        'shipping_refunded' => 'shipping_amount',
        'base_shipping_tax_refunded' => 'base_shipping_tax_amount',
        'shipping_tax_refunded' => 'shipping_tax_amount',
        'base_adjustment_negative',
        'adjustment_negative',
        'base_adjustment_positive',
        'adjustment_positive',
        'discount_refunded' => 'discount_amount',
        'base_discount_refunded' => 'base_discount_amount',
        'base_total_online_refunded' => 'base_grand_total',
        'total_online_refunded' => 'grand_total',
    ];

    /**
     * Cancelled constructor.
     * @param TransactionManager $transactionManager
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        TransactionManager $transactionManager,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->transactionManager = $transactionManager;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws LocalizedException
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $payment = $order->getPayment();
        /** @var Creditmemo $creditmemo */
        $creditmemo = $this->creditMemoService->getCreditmemo($payment, $ingenicoStatus->id);

        if ($creditmemo->getId()) {
            $this->applyCreditmemo($creditmemo);
            $payment->setIsRefundCancellationInProgress(true);
            $payment->cancelCreditmemo($creditmemo);
            $this->closeRefundTransaction(
                $creditmemo
            );
            $this->resetInvoice($creditmemo);
            $this->resetItems(
                $order,
                $creditmemo
            );
            $this->resetOrder(
                $order,
                $creditmemo
            );
        }

        $order->addRelatedObject($creditmemo);
        $order->addRelatedObject($creditmemo->getInvoice());

        $this->orderRepository->save($order);
    }

    /**
     * @param CreditmemoInterface $creditmemo
     */
    public function applyCreditmemo(CreditmemoInterface $creditmemo)
    {
        $creditmemo->setState(Creditmemo::STATE_CANCELED);
    }

    /**
     * Closes the refund transaction for the given creditmemo
     *
     * @param Creditmemo $creditmemo
     */
    private function closeRefundTransaction(Creditmemo $creditmemo)
    {
        $refundTransaction = $this->transactionManager->retrieveTransaction($creditmemo->getTransactionId());
        if ($refundTransaction !== null) {
            $refundTransaction->setIsClosed(true);
        }
    }

    /**
     * Reset invoice amounts
     *
     * @param Creditmemo $creditmemo
     */
    private function resetInvoice($creditmemo)
    {
        /** @var Order\Invoice $invoice */
        $invoice = $this->invoiceRepository->get($creditmemo->getInvoiceId());
        $invoice->setIsUsedForRefund(0)
                ->setBaseTotalRefunded($invoice->getBaseTotalRefunded() - $creditmemo->getBaseGrandTotal());
        $creditmemo->setInvoice($invoice);
    }

    /**
     * Reset ordered items amounts
     *
     * @param Order $order
     * @param Creditmemo $creditmemo
     */
    private function resetItems($order, $creditmemo)
    {
        /** @var Creditmemo\Item $creditmemoItem */
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            // Working directly on the orderItem from the creditmemo
            // does not transfer changes into the order object.
            /** @var Order\Item $orderItem */
            $orderItem = $order->getItemById($creditmemoItem->getOrderItem()->getId());
            $orderItem->setAmountRefunded(
                $orderItem->getAmountRefunded() - $creditmemoItem->getRowTotalInclTax()
            );
            $orderItem->setBaseAmountRefunded(
                $orderItem->getBaseAmountRefunded() - $creditmemoItem->getBaseRowTotalInclTax()
            );
            $orderItem->setQtyRefunded($orderItem->getQtyRefunded() - $creditmemoItem->getQty());
        }
    }

    /**
     * Reset order object amounts and state
     *
     * @param Order $order
     * @param Creditmemo $creditmemo
     */
    private function resetOrder($order, $creditmemo)
    {
        $this->resetOrderTotals(
            $order,
            $creditmemo
        );

        if ($order->canShip() || $order->canInvoice()) {
            $order->setState(Order::STATE_PROCESSING);
        }
    }

    /**
     * Reset order totals according to what has been set during creditmemo creation
     * @see Creditmemo::refund for all totals
     *
     * @param Order $order
     * @param Creditmemo $creditmemo
     */
    private function resetOrderTotals(
        Order $order,
        Creditmemo $creditmemo
    ) {
        foreach ($this::$totals as $orderTotal => $creditmemoTotal) {
            if (is_numeric($orderTotal)) {
                $orderTotal = $creditmemoTotal . '_refunded';
            }
            $value = $order->getData($orderTotal) - $creditmemo->getData($creditmemoTotal);
            $order->setData(
                $orderTotal,
                $value
            );
        }
    }
}
