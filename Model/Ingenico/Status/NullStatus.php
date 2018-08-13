<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

class NullStatus implements HandlerInterface
{
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $orderStatus)
    {
        throw new LocalizedException(__('Status is not implemented'));
    }
}
