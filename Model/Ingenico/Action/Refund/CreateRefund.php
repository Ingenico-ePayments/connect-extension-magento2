<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund\RefundRequestBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\CallContextBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Refund\ResolverInterface;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class CreateRefund
 *
 * @package Ingenico\Connect\Model\Ingenico\Action\Refund
 */
class CreateRefund extends AbstractRefundAction
{
    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var RefundRequestBuilder
     */
    private $refundRequestBuilder;

    /**
     * @var CallContextBuilder
     */
    private $callContextBuilder;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        ClientInterface $ingenicoClient,
        ResolverInterface $statusResolver,
        RefundRequestBuilder $refundRequestBuilder,
        CallContextBuilder $callContextBuilder,
        TransactionRepositoryInterface $transactionRepository,
        TransactionManager $transactionManager
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->statusResolver = $statusResolver;
        $this->refundRequestBuilder = $refundRequestBuilder;
        $this->callContextBuilder = $callContextBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->ingenicoClient = $ingenicoClient;
        $this->transactionManager = $transactionManager;
    }

    /**
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditMemo
     * @throws LocalizedException
     */
    protected function performRefundAction(OrderInterface $order, CreditmemoInterface $creditMemo)
    {
        $payment = $order->getPayment();
        $amount = $creditMemo->getBaseGrandTotal();
        $paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        $request = $this->refundRequestBuilder->build($order, (float) $amount);
        $callContext = $this->callContextBuilder->create();

        $response = $this->ingenicoClient->ingenicoRefund(
            $paymentId,
            $request,
            $callContext,
            $order->getStoreId()
        );

        // Attach transaction to payment:
        $payment->setLastTransId($response->id);
        // @todo: setTransactionId() does not exist on OrderPaymentInterface:
        $payment->setTransactionId($response->id);

        // Create a transaction for the refund:
        $creditMemo->setTransactionId($response->id);

        $transaction = $payment->addTransaction(Transaction::TYPE_REFUND);
        $this->transactionManager->setResponseDataOnTransaction($response, $transaction);

        // Set the parent transaction:
        // @todo: getInvoice() does not exist in CreditmemoInterface:
        $invoice = $creditMemo->getInvoice();
        $captureTxn = $this->transactionRepository->getByTransactionId(
            $invoice->getTransactionId(),
            $payment->getId(),
            $order->getEntityId()
        );

        if ($captureTxn) {
            $transaction->setParentTxnId($captureTxn->getTxnId());
            $payment->setParentTransactionId($captureTxn->getTxnId());
            $payment->setShouldCloseParentTransaction(true);
        }

        // @todo: do this cleaner:
        if ($creditMemo instanceof Creditmemo) {
            // This temporary transaction is set on the credit memo in
            // the case of a new credit memo; in this scenario the credit
            // memo is still in the creation process and the transaction
            // cannot yet be found by using the default Magento methods
            // probably use the registry for this instead?
            // @see the various refund status handlers
            $creditMemo->setData('tmp_transaction', $transaction);
        }

        $this->statusResolver->resolve($creditMemo, $response);

        $payment->setPreparedMessage(
            sprintf(
                'Successfully processed notification about status %s with statusCode %s.',
                $response->status,
                $response->statusOutput->statusCode
            )
        );
    }
}
