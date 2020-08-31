<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund\ApproveRefundRequestBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Refund\Handler\RefundRequested;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Ingenico\Connect\Sdk\Domain\Refund\ApproveRefundRequestFactory;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;

class ApproveRefund extends AbstractRefundAction
{
    /**
     * @var RefundRequested
     */
    private $refundRequestedHandler;

    /**
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var ApproveRefundRequestBuilder
     */
    private $approveRefundRequestBuilder;

    /**
     * ApproveRefund constructor.
     *
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ClientInterface $ingenicoClient
     * @param ApproveRefundRequestBuilder $approveRefundRequestBuilder
     * @param RefundRequested $refundRequestedHandler
     * @param TransactionManager $transactionManager
     */
    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        OrderRepositoryInterface $orderRepository,
        ClientInterface $ingenicoClient,
        ApproveRefundRequestBuilder $approveRefundRequestBuilder,
        RefundRequested $refundRequestedHandler,
        TransactionManager $transactionManager
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->ingenicoClient = $ingenicoClient;
        $this->approveRefundRequestBuilder = $approveRefundRequestBuilder;
        $this->refundRequestedHandler = $refundRequestedHandler;
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
        $this->validateApprovability($refundId);

        // Approve refund via Ingenico API:
        try {
            $request = $this->approveRefundRequestBuilder->build($creditMemo);
            $this->ingenicoClient->ingenicoRefundAccept(
                $creditMemo->getTransactionId(),
                $request,
                $creditMemo->getStoreId()
            );
        } catch (ResponseException $exception) {
            throw new LocalizedException(
                __('Error while trying to approve the refund: %1', $exception->getMessage())
            );
        }

        // If no exception is thrown it means that the API returned a valid
        // and we can assume the refund is approved.
        $this->refundRequestedHandler->applyCreditmemo($creditMemo);
    }

    /**
     * @param string $refundId
     * @throws LocalizedException
     */
    private function validateApprovability(string $refundId)
    {
        $transaction = $this->transactionManager->retrieveTransaction($refundId);
        $refundResponse = $this->transactionManager->getResponseDataFromTransaction($transaction);

        if (!$refundResponse instanceof RefundResult) {
            throw new LocalizedException(__('Stored response data is not a refund response'));
        }

        if ($refundResponse->status !== StatusInterface::PENDING_APPROVAL) {
            throw new LocalizedException(
                __('Cannot approve refund with status %1', $refundResponse->status)
            );
        }
    }
}
