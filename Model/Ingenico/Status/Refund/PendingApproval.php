<?php

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
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
     * PendingApproval constructor.
     * @param TransactionManager $transactionManager
     * @param Service $creditMemoService
     */
    public function __construct(TransactionManager $transactionManager, Service $creditMemoService)
    {
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
        $creditmemo = $this->creditMemoService->getCreditmemo($payment, $ingenicoStatus->id);
        if ($creditmemo->getId()) {
            $this->applyCreditmemo($creditmemo);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function applyCreditmemo(CreditmemoInterface $creditmemo)
    {
        /** @TODO(nr): Gateway\CanRefund checks if status is appropriate for approval. */
        $creditmemo->setState(Creditmemo::STATE_OPEN);
        $transaction = $this->transactionManager->retrieveTransaction($creditmemo->getTransactionId());
        if ($transaction !== null) {
            $transaction->setIsClosed(false);
            $this->transactionManager->updateTransaction($transaction);
        }
    }
}
