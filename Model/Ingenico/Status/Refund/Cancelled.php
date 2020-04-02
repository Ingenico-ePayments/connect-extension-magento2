<?php

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
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
     * Cancelled constructor.
     *
     * @param TransactionManager $transactionManager
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Service $creditMemoService,
        TransactionManager $transactionManager,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->transactionManager = $transactionManager;
        $this->creditMemoService = $creditMemoService;
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
        }
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @param TransactionInterface|null $transaction
     * @return void
     */
    public function applyCreditmemo(CreditmemoInterface $creditMemo, TransactionInterface $transaction = null)
    {
        $creditMemo->setState(Creditmemo::STATE_CANCELED);
        if ($creditMemo->getOrder()->canUnhold()) {
            $creditMemo->getOrder()->unhold();
        }

        if ($transaction === null) {
            $transaction = $this->transactionManager->retrieveTransaction($creditMemo->getTransactionId());
        }

        // Close transaction:
        if ($transaction !== null) {
            $transaction->setIsClosed(true);
            $creditMemo->getOrder()->addRelatedObject($transaction);
        }
    }
}
