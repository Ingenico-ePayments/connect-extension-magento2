<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund\RefundRequestBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Refund\PendingApproval;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\CallContextBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Refund\RefundHandlerInterface;
use Ingenico\Connect\Model\Ingenico\Status\ResolverInterface;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Magento\Sales\Model\Order\Payment;
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
    private $refundRequestbuilder;

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

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        ClientInterface $ingenicoClient,
        ResolverInterface $statusResolver,
        RefundRequestBuilder $refundRequestBuilder,
        CallContextBuilder $callContextBuilder,
        TransactionRepositoryInterface $transactionRepository,
        TransactionManager $transactionManager,
        ManagerInterface $messageManager
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->statusResolver = $statusResolver;
        $this->refundRequestbuilder = $refundRequestBuilder;
        $this->callContextBuilder = $callContextBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->ingenicoClient = $ingenicoClient;
        $this->transactionManager = $transactionManager;
        $this->messageManager = $messageManager;
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

        $request = $this->refundRequestbuilder->build($order, (float) $amount);
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

        /** @var RefundHandlerInterface $handler */
        $handler = $this->statusResolver->getHandlerByType(ResolverInterface::TYPE_REFUND, $response->status);

        // This handler can either be the PENDING_APPROVAL or REFUND_REQUESTED-handler:
        $handler->applyCreditmemo($creditMemo, $transaction);

        $payment->setPreparedMessage(
            sprintf(
                'Successfully processed notification about status %s with statusCode %s.',
                $response->status,
                $response->statusOutput->statusCode
            )
        );

        if ($handler instanceof PendingApproval) {
            $this->messageManager->addNoticeMessage(
            //phpcs:ignore Generic.Files.LineLength.TooLong
                __('It appears that your account at Ingenico is configured that refunds require approval, please contact us')
            );
        }
    }
}
