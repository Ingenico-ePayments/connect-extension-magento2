<?php

namespace Ingenico\Connect\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface EventRepositoryInterface
 *
 * @package Ingenico\Connect\Api
 */
interface EventRepositoryInterface
{

    /**
     * Save Event
     *
     * @param \Ingenico\Connect\Api\Data\EventInterface $event
     * @return \Ingenico\Connect\Api\Data\EventInterface
     * @throws CouldNotSaveException
     */
    public function save(\Ingenico\Connect\Api\Data\EventInterface $event);

    /**
     * Retrieve Event
     *
     * @param string $eventId
     * @return \Ingenico\Connect\Api\Data\EventInterface
     * @throws NoSuchEntityException
     */
    public function getByEventId($eventId);

    /**
     * Retrieve Event
     *
     * @param string $orderIncrementId
     * @return \Ingenico\Connect\Api\Data\EventSearchResultsInterface
     */
    public function getListByOrderIncrementId($orderIncrementId);

    /**
     * Retrieve Event matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Ingenico\Connect\Api\Data\EventSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete Event
     *
     * @param \Ingenico\Connect\Api\Data\EventInterface $event
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(\Ingenico\Connect\Api\Data\EventInterface $event);

    /**
     * Delete Event by ID
     *
     * @param string $eventId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($eventId);
}
