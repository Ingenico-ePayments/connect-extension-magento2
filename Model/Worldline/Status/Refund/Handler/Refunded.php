<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund\Handler;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Status\Refund\HandlerInterface;

class Refunded extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'refunded';

    /**
     * @var TransactionManager
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $transactionManager;

    /**
     * Refunded constructor.
     *
     * @param TransactionManager $transactionManager
     */
    public function __construct(
        TransactionManager $transactionManager,
        ManagerInterface $eventManager,
        ConfigInterface $config
    ) {
        parent::__construct($eventManager, $config);
        $this->transactionManager = $transactionManager;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Creditmemo $creditMemo, RefundResult $worldlineStatus)
    {
        $this->applyCreditmemo($creditMemo);
        $this->addCreditmemoComment($creditMemo, $worldlineStatus);
        $this->dispatchEvent($creditMemo, $worldlineStatus);
    }

    /**
     * {@inheritDoc}
     */
    public function applyCreditmemo(CreditmemoInterface $creditMemo)
    {
        $creditMemo->setState(Creditmemo::STATE_REFUNDED);

        $transaction = $creditMemo->getData('tmp_transaction');
        if ($transaction === null) {
            $transaction = $this->transactionManager->retrieveTransaction($creditMemo->getTransactionId());
        }

        if ($transaction !== null) {
            $transaction->setIsClosed(true);
            $this->transactionManager->updateTransaction($transaction);
        }
    }
}
