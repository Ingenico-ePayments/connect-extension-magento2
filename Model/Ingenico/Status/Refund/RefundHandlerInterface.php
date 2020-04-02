<?php

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Ingenico\Connect\Model\Ingenico\Status\HandlerInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Magento\Sales\Api\Data\TransactionInterface;

interface RefundHandlerInterface extends HandlerInterface
{
    const REFUND_CREATED = 'CREATED';
    const REFUND_PENDING_APPROVAL = 'PENDING_APPROVAL';
    const REFUND_REJECTED = 'REJECTED';
    const REFUND_REFUND_REQUESTED = 'REFUND_REQUESTED';
    const REFUND_CAPTURED = 'CAPTURED';
    const REFUND_REFUNDED = 'REFUNDED';
    const REFUND_CANCELLED = 'CANCELLED';

    /**
     * @param CreditmemoInterface $creditMemo
     * @param TransactionInterface|null $transaction
     * @return void
     */
    public function applyCreditmemo(
        CreditmemoInterface $creditMemo,
        TransactionInterface $transaction = null
    );
}
