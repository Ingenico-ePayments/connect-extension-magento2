<?php

namespace Netresearch\Epayments\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Netresearch\Epayments\Model\Order\Creditmemo\Service;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

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
        }
    }

    /**
     * {@inheritDoc}
     */
    public function applyCreditmemo(CreditmemoInterface $creditmemo)
    {
        $creditmemo->setState(Creditmemo::STATE_REFUNDED);
        $transaction = $this->transactionManager->retrieveTransaction($creditmemo->getTransactionId());
        if ($transaction !== null) {
            $transaction->setIsClosed(true);
            $this->transactionManager->updateTransaction($transaction);
        }
    }
}
