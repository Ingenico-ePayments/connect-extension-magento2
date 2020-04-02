<?php

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Model\Transaction\TransactionManager;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Ingenico\Connect\Model\Order\Creditmemo\Service;
use Magento\Sales\Model\Order\RefundAdapterInterface;

class RefundRequested implements RefundHandlerInterface
{
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
     * RefundRequested constructor.
     *
     * @param Service $creditMemoService
     */
    public function __construct(
        Service $creditMemoService,
        RefundAdapterInterface $refundAdapter,
        TransactionManager $transactionManager,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->creditMemoService = $creditMemoService;
        $this->refundAdapter = $refundAdapter;
        $this->transactionManager = $transactionManager;
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
        /** @var Creditmemo $creditmemo */
        $creditmemo = $this->creditMemoService->getCreditmemo($payment, $ingenicoStatus->id);

        if ($creditmemo->getId()) {
            $this->applyCreditmemo($creditmemo);
        }
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @param TransactionInterface|null $transaction
     * @throws LocalizedException
     */
    public function applyCreditmemo(CreditmemoInterface $creditMemo, TransactionInterface $transaction = null)
    {
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

        // Process refund using the default Magento refund adapter:
        $creditMemo->setPaymentRefundDisallowed(true);
        $creditMemo->setRefundTransaction($transaction);
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
