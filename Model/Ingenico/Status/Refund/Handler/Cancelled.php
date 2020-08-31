<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Refund\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Refund\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Ingenico\Connect\Model\Order\Creditmemo\Service;
use Ingenico\Connect\Model\Transaction\TransactionManager;

class Cancelled extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'cancelled';

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
     * @param Service $creditMemoService
     * @param TransactionManager $transactionManager
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Service $creditMemoService,
        TransactionManager $transactionManager,
        InvoiceRepositoryInterface $invoiceRepository,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $eventManager
    ) {
        parent::__construct($eventManager);
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->transactionManager = $transactionManager;
        $this->creditMemoService = $creditMemoService;
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
     * @param CreditmemoInterface $creditMemo
     * @throws LocalizedException
     */
    public function applyCreditmemo(CreditmemoInterface $creditMemo)
    {
        $creditMemo->setState(Creditmemo::STATE_CANCELED);

        if ($creditMemo instanceof Creditmemo) {
            if ($creditMemo->getOrder()->canUnhold()) {
                $creditMemo->getOrder()->unhold();
            }
            $transaction = $creditMemo->getData('tmp_transaction');
        } else {
            $transaction = null;
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
