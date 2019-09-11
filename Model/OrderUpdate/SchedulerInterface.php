<?php

namespace Ingenico\Connect\Model\OrderUpdate;

use Magento\Sales\Model\Order as MagentoOrder;

interface SchedulerInterface
{
    /**
     * Decide if it's time to pull payment from ingenico
     *
     * @param Order $order
     * @return bool
     */
    public function timeForAttempt(MagentoOrder $order);


    /**
     * Decide if it's time for WR
     *
     * @param Order $order
     * @return bool
     */
    public function timeForWr(MagentoOrder $order);
}
