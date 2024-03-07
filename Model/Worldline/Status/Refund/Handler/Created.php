<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund\Handler;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Sales\Api\CreditmemoManagementInterface as CreditmemoManager;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Convert\Order as OrderConvertor;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface as TransactionManagerInterface;
use Worldline\Connect\Helper\Data;
use Worldline\Connect\Model\Worldline\Status\Refund\HandlerInterface;

use function __;
use function str_replace;

class Created implements HandlerInterface
{
    public function __construct(
        private readonly CreditmemoManager $creditmemoManager,
        private readonly OrderConvertor $convertor,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {
    }

    /**
     * @param Order $order
     * @param RefundResult $status
     * @return void
     * @see Payment::registerRefundNotification()
     */
    public function resolveStatus(Order $order, RefundResult $status)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        $payment->setParentTransactionId(str_replace('-', '0', $status->id));

        $amount = Data::reformatMagentoAmount($status->refundOutput->amountOfMoney->amount);

        $this->registerRefundNotification($payment, $amount);
    }

    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    private function registerRefundNotification(Payment $payment, float $amount): void
    {
        $order = $payment->getOrder();
        $payment->setTransactionId(
            $this->transactionManager->generateTransactionId(
                $payment,
                Transaction::TYPE_REFUND,
                $this->transactionRepository->getByTransactionId(
                    $payment->getParentTransactionId(),
                    $payment->getId(),
                    $order->getId()
                )
            )
        );

        $creditmemo = $this->convertor->toCreditmemo($order);
        $creditmemo->setPaymentRefundDisallowed(
            true
        )->setAutomaticallyCreated(
            true
        )->addComment(
            __('The credit memo has been created automatically.')
        );
        $creditmemo->setAdjustmentPositive($amount);
        $creditmemo->setShippingAmount(0.0);
        $creditmemo->collectTotals();

        $this->creditmemoManager->refund($creditmemo, false);

        $payment->setAmountRefunded($payment->getAmountRefunded() + $creditmemo->getGrandTotal());
        $payment->setBaseAmountRefundedOnline($payment->getBaseAmountRefundedOnline() + $amount);
        $payment->setCreatedCreditmemo($creditmemo);

        // update transactions and order state
        $transaction = $payment->addTransaction(
            Transaction::TYPE_REFUND,
            $creditmemo
        );
        $message = $payment->prependMessage(
            __('Registered notification about refunded amount of %1.', $payment->formatPrice($amount))
        ) . ' ' . __('Transaction ID: "%1"', $transaction->getHtmlTxnId());

        $order->setState(Order::STATE_PROCESSING)
            ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
            ->addStatusHistoryComment($message);
    }
}
