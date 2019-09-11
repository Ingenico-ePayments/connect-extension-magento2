<?php

namespace Ingenico\Connect\Model\ResourceModel\Event;

/**
 * Class Collection
 *
 * @package Ingenico\Connect\Model\ResourceModel\Event
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Ingenico\Connect\Model\Event::class,
            \Ingenico\Connect\Model\ResourceModel\Event::class
        );
    }
}
