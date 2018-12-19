<?php

namespace Netresearch\Epayments\Api\Data;

/**
 * Interface EventSearchResultsInterface
 *
 * @package Netresearch\Epayments\Api\Data
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @link http://www.netresearch.de/
 */
interface EventSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Event list.
     *
     * @return \Netresearch\Epayments\Api\Data\EventInterface[]
     */
    public function getItems();

    /**
     * Set event_id list.
     *
     * @param \Netresearch\Epayments\Api\Data\EventInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
