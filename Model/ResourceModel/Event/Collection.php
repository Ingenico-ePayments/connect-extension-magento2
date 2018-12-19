<?php

namespace Netresearch\Epayments\Model\ResourceModel\Event;

/**
 * Class Collection
 *
 * @package Netresearch\Epayments\Model\ResourceModel\Event
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @link http://www.netresearch.de/
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
            \Netresearch\Epayments\Model\Event::class,
            \Netresearch\Epayments\Model\ResourceModel\Event::class
        );
    }
}
