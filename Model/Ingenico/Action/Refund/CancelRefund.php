<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Model\Ingenico\Status\Refund\Handler\Cancelled;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Ingenico\Connect\Model\Ingenico\Action\RetrievePayment;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;

class CancelRefund extends AbstractRefundAction
{
    /**
     * @var RetrievePayment
     */
    private $retrievePayment;

    /**
     * @var Cancelled
     */
    private $refundCancelledHandler;

    /**
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * CancelRefund constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param ClientInterface $ingenicoClient
     * @param RetrievePayment $retrievePayment
     * @param Cancelled $refundCancelledHandler
     * @param TransactionManager $transactionManager
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        ClientInterface $ingenicoClient,
        RetrievePayment $retrievePayment,
        Cancelled $refundCancelledHandler,
        TransactionManager $transactionManager
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->retrievePayment = $retrievePayment;
        $this->refundCancelledHandler = $refundCancelledHandler;
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
        $refundId = $creditMemo->getTransactionId();
        $this->validateCancellability($refundId);

        // Cancel refund via Ingenico API:
        try {
            $this->ingenicoClient->ingenicoRefundCancel(
                $refundId,
                $order->getStoreId()
            );
        } catch (ResponseException $exception) {
            throw new LocalizedException(
                __('Error while trying to cancel the refund: %1', $exception->getMessage())
            );
        }

        // If no exception is thrown it means that the API returned a valid
        // and we can assume the refund is cancelled.
        $this->refundCancelledHandler->applyCreditmemo($creditMemo);
    }

    /**
     * @param string $refundId
     * @throws LocalizedException
     */
    private function validateCancellability(string $refundId)
    {
        $transaction = $this->transactionManager->retrieveTransaction($refundId);
        $refundResponse = $this->transactionManager->getResponseDataFromTransaction($transaction);

        if (!$refundResponse instanceof RefundResult) {
            throw new LocalizedException(__('Stored response data is not a refund response'));
        }

        if (!$refundResponse->statusOutput->isCancellable) {
            throw new LocalizedException(__("Cannot cancel refund with status $refundResponse->status"));
        }
    }
}
