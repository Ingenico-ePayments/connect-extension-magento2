<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund\Handler;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Status\Refund\HandlerInterface;

class PendingApproval extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_approval';

    /**
     * @var TransactionManager
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $transactionManager;

    /**
     * @var ManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $messageManager;

    /**
     * PendingApproval constructor.
     *
     * @param TransactionManager $transactionManager
     * @param ManagerInterface $messageManager
     * @param EventManagerInterface $eventManager
     * @param ConfigInterface $config
     */
    public function __construct(
        TransactionManager $transactionManager,
        ManagerInterface $messageManager,
        EventManagerInterface $eventManager,
        ConfigInterface $config
    ) {
        parent::__construct($eventManager, $config);
        $this->transactionManager = $transactionManager;
        $this->messageManager = $messageManager;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Creditmemo $creditMemo, RefundResult $worldlineStatus)
    {
        $this->applyCreditmemo($creditMemo, $worldlineStatus);
        $this->addCreditmemoComment($creditMemo, $worldlineStatus);

        $this->messageManager->addNoticeMessage(
        //phpcs:ignore Generic.Files.LineLength.TooLong, SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            __('It appears that your account at Worldline is configured that refunds require approval, please contact us')
        );

        $this->dispatchEvent($creditMemo, $worldlineStatus);
    }

    public function applyCreditmemo(Creditmemo $creditMemo, RefundResult $worldlineStatus)
    {
        $this->validateCreditMemo($creditMemo);

        $creditMemo->setState(Creditmemo::STATE_OPEN);
        $order = $creditMemo->getOrder();

        // Put the order on hold when a refund requires approval:
        if ($order->canHold()) {
            $order->hold();
        }

        /** @var Payment $payment */
        $payment = $creditMemo->getOrder()->getPayment();
        $payment->setLastTransId($worldlineStatus->id);
        $payment->setTransactionId($worldlineStatus->id);
        $creditMemo->setTransactionId($worldlineStatus->id);

        // Create transaction object:
        $transaction = $payment->addTransaction(Transaction::TYPE_REFUND);

        $invoice = $creditMemo->getInvoice();
        if ($captureTxn = $this->transactionManager->retrieveTransaction($invoice->getTransactionId())) {
            $transaction->setParentTxnId($captureTxn->getTxnId());
            $payment->setParentTransactionId($captureTxn->getTxnId());
            $payment->setShouldCloseParentTransaction(true);
        }

        $transaction->setIsClosed(false);
        $this->transactionManager->updateTransaction($transaction);
    }
}
