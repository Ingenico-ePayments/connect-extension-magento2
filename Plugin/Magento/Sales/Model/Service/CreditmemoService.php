<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Model\Service;

use Exception;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\Connect\Model\Ingenico\Action\Refund\CreateRefund;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\CreditmemoService as MagentoCreditmemoService;

class CreditmemoService
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var CreateRefund
     */
    private $createRefundAction;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $paymentRepository,
        CreateRefund $createRefundAction,
        ResourceConnection $resourceConnection
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->createRefundAction = $createRefundAction;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * A refund in Ingenico is actually a request for a refund. This can
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
        if (!$this->isOrderPaidWithIngenico((int) $creditmemo->getOrderId())) {
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
            throw new LocalizedException(__($exception->getMessage()));
        }

        return $creditmemo;
    }

    private function isOrderPaidWithIngenico(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);
        return $order->getPayment()->getMethod() === ConfigProvider::CODE;
    }
}
