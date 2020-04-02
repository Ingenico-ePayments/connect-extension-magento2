<?php

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Ingenico\Connect\Model\Order\Creditmemo\Service;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Magento\Sales\Model\Order\Payment;

class Refunded implements RefundHandlerInterface
{
    /**
     * @var Service
     */
    private $creditMemoService;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * Refunded constructor.
     *
     * @param Service $creditMemoService
     * @param TransactionManager $transactionManager
     */
    public function __construct(Service $creditMemoService, TransactionManager $transactionManager)
    {
        $this->creditMemoService = $creditMemoService;
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
            // We need to attach the credit memo for persisting later on:
            $payment = $order->getPayment();
            if ($payment instanceof Payment) {
                $payment->setCreditmemo($creditmemo);
            }
        }

        if ($order->canUnhold()) {
            $order->unhold();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function applyCreditmemo(CreditmemoInterface $creditMemo, TransactionInterface $transaction = null)
    {
        $creditMemo->setState(Creditmemo::STATE_REFUNDED);

        if ($transaction === null) {
            $transaction = $this->transactionManager->retrieveTransaction($creditMemo->getTransactionId());
        }

        if ($transaction !== null) {
            $transaction->setIsClosed(true);
            $this->transactionManager->updateTransaction($transaction);
        }
    }
}
