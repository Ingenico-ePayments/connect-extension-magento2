<?php

namespace Ingenico\Connect\Model\OrderUpdate;

use Magento\Sales\Model\Order as MagentoOrder;

interface HistoryManagerInterface
{
    /**
     * Add attempt data to api history
     *
     * @param MagentoOrder $order
     * @param array $data
     * @param string $historyType
     */
    public function addHistory(
        MagentoOrder $order,
        array $data,
        $historyType
    );
}
