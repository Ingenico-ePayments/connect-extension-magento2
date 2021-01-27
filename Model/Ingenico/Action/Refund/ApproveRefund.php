<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Api\RefundManagementInterface;
use Ingenico\Connect\Model\Ingenico\Status\Refund\ResolverInterface;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
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
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var RefundManagementInterface
     */
    private $refundManagement;

    /**
     * @var ResolverInterface
     */
    private $refundStatusResolver;

    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        OrderRepositoryInterface $orderRepository,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        RefundManagementInterface $refundManagement,
        ResolverInterface $refundStatusResolver
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->ingenicoClient = $ingenicoClient;
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
        // @TODO: replace stored response object with status meta-data in aditional information:
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
