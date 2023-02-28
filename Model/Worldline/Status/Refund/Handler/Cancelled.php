<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund\Handler;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Status\Refund\HandlerInterface;

class Cancelled extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'cancelled';

    /**
      * @var TransactionManager
      */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $transactionManager;

    /**
     * Cancelled constructor.
     *
     * @param TransactionManager $transactionManager
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $eventManager
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
     * @throws LocalizedException
     */
    public function applyCreditmemo(Creditmemo $creditMemo)
    {
        $creditMemo->setState(Creditmemo::STATE_CANCELED);

        if ($creditMemo->getOrder()->canUnhold()) {
            $creditMemo->getOrder()->unhold();
        }

        $transaction = $creditMemo->getData('tmp_transaction');
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
