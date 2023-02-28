<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Worldline\Connect\Model\Order\Payment\OrderPaymentManagement;
use Worldline\Connect\Model\StatusResponseManagerInterface;
use Worldline\Connect\Model\Worldline\Status\AbstractResolver;

class Resolver extends AbstractResolver implements ResolverInterface
{
    protected const KEY_STATUS = OrderPaymentManagement::KEY_REFUND_STATUS;
    protected const KEY_STATUS_CODE_CHANGE_DATE_TIME = OrderPaymentManagement::KEY_REFUND_STATUS_CODE_CHANGE_DATE_TIME;

    /**
     * @var PoolInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusHandlerPool;

    /**
     * @var OrderRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    public function __construct(
        StatusResponseManagerInterface $statusResponseManager,
        PoolInterface $statusHandlerPool,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($statusResponseManager);
        $this->statusHandlerPool = $statusHandlerPool;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @param RefundResult $status
     * @throws LocalizedException
     * @throws NotFoundException
     */
    public function resolve(CreditmemoInterface $creditMemo, RefundResult $status)
    {
        $order = $this->getOrder($creditMemo);

        if (!$this->isStatusNewerThanPreviousStatus($order, $status)) {
            return;
        }

        $this->preparePayment($order->getPayment(), $status);
        // Only run the resolver on an actual status change, otherwise
        // only update the meta-data:
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        $currentStatus = $additionalInformation[self::KEY_STATUS] ?? null;
        if ($status->status !== $currentStatus) {
            $statusHandler = $this->statusHandlerPool->get($status->status);
            $statusHandler->resolveStatus($creditMemo, $status);
        }

        $this->updateStatusCodeChangeDate($order, $status);
        $this->updateStatus($order, $status);
        $this->updatePayment($order->getPayment(), $status);
    }

    private function getOrder(CreditmemoInterface $creditMemo): OrderInterface
    {
        if ($creditMemo instanceof Creditmemo) {
            return $creditMemo->getOrder();
        }

        return $this->orderRepository->get($creditMemo->getOrderId());
    }
}
