<?php

namespace Netresearch\Epayments\Model\ResourceModel;

/**
 * Class Event
 *
 * @package Netresearch\Epayments\Model\ResourceModel
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @link http://www.netresearch.de/
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
