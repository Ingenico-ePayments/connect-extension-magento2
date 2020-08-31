<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Refund\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Refund\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Ingenico\Connect\Model\Order\Creditmemo\Service;
use Ingenico\Connect\Model\Transaction\TransactionManager;

class PendingApproval extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_approval';

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
     * @param ManagerInterface $messageManager
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        TransactionManager $transactionManager,
        Service $creditMemoService,
        OrderRepositoryInterface $orderRepository,
        EventManagerInterface $eventManager
    ) {
        parent::__construct($eventManager);
        $this->transactionManager = $transactionManager;
        $this->creditMemoService = $creditMemoService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(CreditmemoInterface $creditMemo, RefundResult $ingenicoStatus)
    {
        $this->applyCreditmemo($creditMemo);
        $this->dispatchEvent($creditMemo, $ingenicoStatus);
    }

    /**
     * {@inheritDoc}
     */
    public function applyCreditmemo(CreditmemoInterface $creditMemo)
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

        if ($creditMemo instanceof Creditmemo) {
            $transaction = $creditMemo->getData('tmp_transaction');
        } else {
            $transaction = null;
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
