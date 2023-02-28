<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Action\RetrievePayment;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Status\Refund\Handler\Cancelled;

class CancelRefund extends AbstractRefundAction
{
    /**
     * @var RetrievePayment
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $retrievePayment;

    /**
     * @var Cancelled
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $refundCancelledHandler;

    /**
     * @var ClientInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $worldlineClient;

    /**
     * @var TransactionManager
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $transactionManager;

    /**
     * CancelRefund constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param ClientInterface $worldlineClient
     * @param RetrievePayment $retrievePayment
     * @param Cancelled $refundCancelledHandler
     * @param TransactionManager $transactionManager
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        ClientInterface $worldlineClient,
        RetrievePayment $retrievePayment,
        Cancelled $refundCancelledHandler,
        TransactionManager $transactionManager
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->retrievePayment = $retrievePayment;
        $this->refundCancelledHandler = $refundCancelledHandler;
        $this->worldlineClient = $worldlineClient;
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

        // Cancel refund via Worldline API:
        try {
            $this->worldlineClient->worldlineRefundCancel(
                $refundId,
                $order->getStoreId()
            );
        } catch (ResponseException $exception) {
            throw new LocalizedException(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Stored response data is not a refund response'));
        }

        if (!$refundResponse->statusOutput->isCancellable) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName, Squiz.Strings.DoubleQuoteUsage.ContainsVar
            throw new LocalizedException(__("Cannot cancel refund with status $refundResponse->status"));
        }
    }
}
