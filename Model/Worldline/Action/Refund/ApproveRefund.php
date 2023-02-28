<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Worldline\Connect\Api\RefundManagementInterface;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Status\Refund\ResolverInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;

class ApproveRefund extends AbstractRefundAction
{
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
     * @var RefundManagementInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $refundManagement;

    /**
     * @var ResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $refundStatusResolver;

    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        OrderRepositoryInterface $orderRepository,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        RefundManagementInterface $refundManagement,
        ResolverInterface $refundStatusResolver
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->worldlineClient = $worldlineClient;
        $this->transactionManager = $transactionManager;
        $this->refundManagement = $refundManagement;
        $this->refundStatusResolver = $refundStatusResolver;
    }

    /**
     * @param OrderInterface $order
     * @param CreditmemoInterface $creditMemo
     * @throws LocalizedException
     */
    protected function performRefundAction(OrderInterface $order, CreditmemoInterface $creditMemo)
    {
        $this->validateApprovability($creditMemo->getTransactionId());
        $this->refundManagement->approveRefund($creditMemo);
        $refundStatus = $this->refundManagement->fetchRefundStatus($creditMemo);
        $this->refundStatusResolver->resolve($creditMemo, $refundStatus);
    }

    /**
     * @param string $refundId
     * @throws LocalizedException
     */
    private function validateApprovability(string $refundId)
    {
        $transaction = $this->transactionManager->retrieveTransaction($refundId);
        // phpcs:ignore Generic.Commenting.Todo.TaskFound
        // @TODO: replace stored response object with status meta-data in aditional information:
        $refundResponse = $this->transactionManager->getResponseDataFromTransaction($transaction);

        if (!$refundResponse instanceof RefundResult) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Stored response data is not a refund response'));
        }

        if ($refundResponse->status !== StatusInterface::PENDING_APPROVAL) {
            throw new LocalizedException(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __('Cannot approve refund with status %1', $refundResponse->status)
            );
        }
    }
}
