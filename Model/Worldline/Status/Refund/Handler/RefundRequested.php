<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund\Handler;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\RefundAdapterInterface;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Status\Refund\HandlerInterface;

class RefundRequested extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'refund_requested';

    /**
     * @var RefundAdapterInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $refundAdapter;

    /**
     * @var TransactionManager
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $transactionManager;

    /**
     * RefundRequested constructor.
     *
     * @param RefundAdapterInterface $refundAdapter
     * @param TransactionManager $transactionManager
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        RefundAdapterInterface $refundAdapter,
        TransactionManager $transactionManager,
        ManagerInterface $eventManager,
        Config $config
    ) {
        parent::__construct($eventManager, $config);
        $this->refundAdapter = $refundAdapter;
        $this->transactionManager = $transactionManager;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Creditmemo $creditMemo, RefundResult $worldlineStatus)
    {
        $this->applyCreditmemo($creditMemo, $worldlineStatus);
        $this->addCreditmemoComment($creditMemo, $worldlineStatus);
        $this->dispatchEvent($creditMemo, $worldlineStatus);
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @param RefundResult $worldlineStatus
     * @throws LocalizedException
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function applyCreditmemo(Creditmemo $creditMemo, RefundResult $worldlineStatus)
    {
        $this->validateCreditMemo($creditMemo);

        // If the order cannot be unhold, the refund cannot proceed:
        if ($creditMemo->getOrder()->canUnhold()) {
            $creditMemo->getOrder()->unhold();
        }

        // Update transaction ID:
        /** @var Payment $payment */
        $payment = $creditMemo->getOrder()->getPayment();
        $payment->setLastTransId($worldlineStatus->id);
        $payment->setTransactionId($worldlineStatus->id);
        $creditMemo->setTransactionId($worldlineStatus->id);

        // Create transaction object:
        $transaction = $payment->addTransaction(Transaction::TYPE_REFUND);

        // With the default refund flow the REFUND_REQUESTED-status is a guarantee that the refund will succeed.
        $creditMemo->setState(Creditmemo::STATE_REFUNDED);

        // Mark invoice as being refunded:
        $creditMemo->getInvoice()->setIsUsedForRefund(true);
        $creditMemo->getInvoice()->setBaseTotalRefunded($creditMemo->getBaseGrandTotal());
        $creditMemo->getOrder()->addRelatedObject($creditMemo->getInvoice());

        $invoice = $creditMemo->getInvoice();
        if ($captureTxn = $this->transactionManager->retrieveTransaction($invoice->getTransactionId())) {
            $transaction->setParentTxnId($captureTxn->getTxnId());
            $payment->setParentTransactionId($captureTxn->getTxnId());
            $payment->setShouldCloseParentTransaction(true);
        }

        // Process refund using the default Magento refund adapter:
        // Don't allow an additional request to be made to the gateway:
        $creditMemo->setPaymentRefundDisallowed(true);
        $creditMemo->setRefundTransaction($transaction);

        // Set proper message to use instead of "We refunded x Offline":
        $creditMemo->getOrder()
            ->getPayment()
            ->setMessage(
                $this->config->getRefundStatusInfo($worldlineStatus->status) . '.'
            );
//        $this->refundAdapter->refund($creditMemo, $creditMemo->getOrder(), true);

        // Close transaction:
        $transaction->setIsClosed(true);
        $creditMemo->getOrder()->addRelatedObject($transaction);
    }
}
