<?php

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;

class NullStatus implements RefundHandlerInterface
{
    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws LocalizedException
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        throw new LocalizedException(__('Status is not implemented.'));
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @param TransactionInterface|null $transaction
     * @throws LocalizedException
     */
    public function applyCreditmemo(CreditmemoInterface $creditMemo, TransactionInterface $transaction = null)
    {
        throw new LocalizedException(__('Status is not implemented.'));
    }
}
