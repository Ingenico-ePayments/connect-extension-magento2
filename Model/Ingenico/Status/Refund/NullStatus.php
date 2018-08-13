<?php

namespace Netresearch\Epayments\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
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
     * @param CreditmemoInterface $creditmemo
     * @throws LocalizedException
     */
    public function applyCreditmemo(CreditmemoInterface $creditmemo)
    {
        throw new LocalizedException(__('Status is not implemented.'));
    }
}
