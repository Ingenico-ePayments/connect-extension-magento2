<?php

namespace Ingenico\Connect\Model\ResourceModel;

/**
 * Class Event
 *
 * @package Ingenico\Connect\Model\ResourceModel
 */
class Event extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('epayments_webhook_event', 'id');
    }
}
