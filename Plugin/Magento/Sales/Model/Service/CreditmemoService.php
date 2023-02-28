<?php

declare(strict_types=1);

namespace Worldline\Connect\Plugin\Magento\Sales\Model\Service;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\CreditmemoService as MagentoCreditmemoService;
use Worldline\Connect\Model\ConfigProvider;
use Worldline\Connect\Model\Worldline\Action\Refund\CreateRefund;
use Worldline\Connect\Model\Worldline\Action\Refund\RefundActionInterface;

class CreditmemoService
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $creditMemoRepository;

    /**
     * @var OrderRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentRepository;

    /**
     * @var CreateRefund
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $createRefundAction;

    /**
     * @var ResourceConnection
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $resourceConnection;

    public function __construct(
        CreditmemoRepositoryInterface $creditMemoRepository,
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $paymentRepository,
        RefundActionInterface $createRefundAction,
        ResourceConnection $resourceConnection
    ) {
        $this->creditMemoRepository = $creditMemoRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->createRefundAction = $createRefundAction;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * A refund in Worldline is actually a request for a refund. This can
     * have 2 different results:
     *  - PENDING_APPROVAL : A Refund is requested but needs to be approved by the merchant
     *  - REFUND_REQUESTED : A Refund is requested by the carrier
     * In case of a PENDING_APPROVAL the order will be set "on hold".
     * Only the PENDING_APPROVAL is cancellable by the merchant.
     *
     * @param MagentoCreditmemoService $subject
     * @param callable $proceed
     * @param CreditmemoInterface $creditmemo
     * @param bool $offlineRequested
     * @return CreditmemoInterface
     * @throws LocalizedException
     * @link MagentoCreditmemoService::refund()
     */
    public function aroundRefund(
        MagentoCreditmemoService $subject,
        callable $proceed,
        CreditmemoInterface $creditmemo,
        $offlineRequested = false
    ) {
        return $proceed($creditmemo, $offlineRequested);

        if (!$this->isOrderPaidWithWorldline((int) $creditmemo->getOrderId()) || $offlineRequested) {
            return $proceed($creditmemo, $offlineRequested);
        }

        // Wrap in transaction, just like the original refund()-method:
        $connection = $this->resourceConnection->getConnection('sales');
        $connection->beginTransaction();
        try {
            $this->createRefundAction->process($creditmemo);
            $connection->commit();
        } catch (Exception $exception) {
            $connection->rollBack();
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__($exception->getMessage()));
        }

        return $creditmemo;
    }

    private function isOrderPaidWithWorldline(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);
        return $order->getPayment()->getMethod() === ConfigProvider::CODE;
    }
}
