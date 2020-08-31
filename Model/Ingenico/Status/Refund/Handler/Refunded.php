<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Refund\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Refund\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Ingenico\Connect\Model\Order\Creditmemo\Service;
use Ingenico\Connect\Model\Transaction\TransactionManager;

class Refunded extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'refunded';

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
    public function __construct(
        Service $creditMemoService,
        TransactionManager $transactionManager,
        ManagerInterface $eventManager
    ) {
        parent::__construct($eventManager);
        $this->creditMemoService = $creditMemoService;
        $this->transactionManager = $transactionManager;
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
        $creditMemo->setState(Creditmemo::STATE_REFUNDED);

        if ($creditMemo instanceof Creditmemo) {
            $transaction = $creditMemo->getData('tmp_transaction');
        } else {
            $transaction = null;
        }

        if ($transaction === null) {
            $transaction = $this->transactionManager->retrieveTransaction($creditMemo->getTransactionId());
        }

        if ($transaction !== null) {
            $transaction->setIsClosed(true);
            $this->transactionManager->updateTransaction($transaction);
        }
    }
}
