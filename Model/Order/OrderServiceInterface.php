<?php

namespace Ingenico\Connect\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;

interface OrderServiceInterface
{
    /**
     * @param int $incrementId
     * @return OrderInterface
     */
    public function getByIncrementId($incrementId);
}
