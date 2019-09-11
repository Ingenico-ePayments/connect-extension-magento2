<?php

namespace Ingenico\Connect\Model\ResourceModel\IngenicoPendingOrder;

use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\ConfigProvider;

class Collection extends \Magento\Sales\Model\ResourceModel\Order\Collection
{

    /**
     * Add filter orders for real-time api call update
     *
     * @param $scopeId
     * @return $this
     */
    public function addRealTimeUpdateOrdersFilter($scopeId)
    {
        $this->buildFilter($scopeId);
        $this->addFieldToFilter("order_update_wr_status", ['null' => true]);

        return $this;
    }

    /**
     * Add filter orders for .wr file processing
     *
     * @param int $scopeId
     * @return $this
     */
    public function addWrFileUpdateOrdersFilter($scopeId)
    {
        $this->buildFilter($scopeId);
        $this->addFieldToFilter("order_update_wr_status", \Ingenico\Connect\Model\OrderUpdate\Order::STATUS_WAIT);

        return $this;
    }

    /**
     * Build filter skeleton
     *
     * @param int $scopeId
     */
    private function buildFilter($scopeId)
    {
        $this->addFieldToFilter('status', Order::STATE_PENDING_PAYMENT);
        $this->addFieldToFilter('method', ConfigProvider::CODE);
        $this->addFieldToFilter('store_id', $scopeId);

        $onCondition = "main_table.entity_id = sales_order_payment.parent_id";
        $this->getSelect()->join('sales_order_payment', $onCondition)->order("main_table.entity_id", 'ASC');
    }
}
