<?php

namespace Ingenico\Connect\Api\Data;

/**
 * Interface EventSearchResultsInterface
 *
 * @package Ingenico\Connect\Api\Data
 */
interface EventSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Event list.
     *
     * @return \Ingenico\Connect\Api\Data\EventInterface[]
     */
    public function getItems();

    /**
     * Set event_id list.
     *
     * @param \Ingenico\Connect\Api\Data\EventInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
