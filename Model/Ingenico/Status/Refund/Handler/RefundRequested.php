<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Refund\Handler;

use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\Status\Refund\HandlerInterface;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use LogicException;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Ingenico\Connect\Model\Order\Creditmemo\Service;
use Magento\Sales\Model\Order\RefundAdapterInterface;

class RefundRequested extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'refund_requested';

    /**
     * @var Service
     */
    private $creditMemoService;

    /**
     * @var RefundAdapterInterface
     */
    private $refundAdapter;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * RefundRequested constructor.
     *
     * @param Service $creditMemoService
     * @param RefundAdapterInterface $refundAdapter
     * @param TransactionManager $transactionManager
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Service $creditMemoService,
        RefundAdapterInterface $refundAdapter,
        TransactionManager $transactionManager,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $eventManager,
        Config $config
    ) {
        parent::__construct($eventManager);
        $this->creditMemoService = $creditMemoService;
        $this->refundAdapter = $refundAdapter;
        $this->transactionManager = $transactionManager;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(CreditmemoInterface $creditMemo, RefundResult $ingenicoStatus)
    {
        $this->applyCreditmemo($creditMemo, $ingenicoStatus);
        $this->dispatchEvent($creditMemo, $ingenicoStatus);
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @param RefundResult $ingenicoStatus
     * @throws LocalizedException
     */
    public function applyCreditmemo(CreditmemoInterface $creditMemo, RefundResult $ingenicoStatus)
    {
        if (!$creditMemo instanceof Creditmemo) {
            throw new LogicException('Only ' . Creditmemo::class . ' is supported');
        }

        /** @var Creditmemo $creditMemo */
        // If the order cannot be unhold, the refund cannot proceed:
        if ($creditMemo->getOrder()->canUnhold()) {
            $creditMemo->getOrder()->unhold();
        }

        // With the default refund flow the REFUND_REQUESTED-status is a guarantee that the refund will succeed.
        $creditMemo->setState(Creditmemo::STATE_REFUNDED);

        // Mark invoice as being refunded:
        $creditMemo->getInvoice()->setIsUsedForRefund(true);
        $creditMemo->getInvoice()->setBaseTotalRefunded($creditMemo->getBaseGrandTotal());
        $creditMemo->getOrder()->addRelatedObject($creditMemo->getInvoice());

        if ($creditMemo instanceof Creditmemo) {
            $transaction = $creditMemo->getData('tmp_transaction');
        } else {
            $transaction = null;
        }

        // Process refund using the default Magento refund adapter:
        // Don't allow an additional request to be made to the gateway:
        $creditMemo->setPaymentRefundDisallowed(true);
        $creditMemo->setRefundTransaction($transaction);
        // Set proper message to use instead of "We refunded x Offline":
        $creditMemo->getOrder()
            ->getPayment()
            ->setMessage(
                $this->config->getRefundStatusInfo($ingenicoStatus->status) . '.'
            );
        $this->refundAdapter->refund($creditMemo, $creditMemo->getOrder(), true);

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
