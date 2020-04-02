<?php

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Ingenico\Connect\Model\Order\Creditmemo\Service;
use Ingenico\Connect\Model\Transaction\TransactionManager;

class PendingApproval implements RefundHandlerInterface
{
    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var Service
     */
    private $creditMemoService;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * PendingApproval constructor.
     *
     * @param TransactionManager $transactionManager
     * @param Service $creditMemoService
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        TransactionManager $transactionManager,
        Service $creditMemoService,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->transactionManager = $transactionManager;
        $this->creditMemoService = $creditMemoService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws LocalizedException
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $payment = $order->getPayment();
        $creditmemo = $this->creditMemoService->getCreditmemo($payment, $ingenicoStatus->id);
        if ($creditmemo->getId()) {
            $this->applyCreditmemo($creditmemo);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function applyCreditmemo(CreditmemoInterface $creditMemo, TransactionInterface $transaction = null)
    {
        if (!$creditMemo instanceof Creditmemo) {
            return;
        }

        /** @TODO(nr): Gateway\CanRefund checks if status is appropriate for approval. */
        $creditMemo->setState(Creditmemo::STATE_OPEN);
        $order = $creditMemo->getOrder();

        // Put the order on hold when a refund requires approval:
        if ($order->canHold()) {
            $order->hold();
        }

        if ($transaction === null) {
            $transaction = $this->transactionManager->retrieveTransaction($creditMemo->getTransactionId());
        }

        if ($transaction !== null) {
            $transaction->setIsClosed(false);
            $this->transactionManager->updateTransaction($transaction);
        }
    }
}
