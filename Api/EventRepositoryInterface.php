<?php

namespace Netresearch\Epayments\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface EventRepositoryInterface
 *
 * @package Netresearch\Epayments\Api
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @link http://www.netresearch.de/
 */
interface EventRepositoryInterface
{

    /**
     * Save Event
     *
     * @param \Netresearch\Epayments\Api\Data\EventInterface $event
     * @return \Netresearch\Epayments\Api\Data\EventInterface
     * @throws CouldNotSaveException
     */
    public function save(\Netresearch\Epayments\Api\Data\EventInterface $event);

    /**
     * Retrieve Event
     *
     * @param string $eventId
     * @return \Netresearch\Epayments\Api\Data\EventInterface
     * @throws NoSuchEntityException
     */
    public function getByEventId($eventId);

    /**
     * Retrieve Event
     *
     * @param string $orderIncrementId
     * @return \Netresearch\Epayments\Api\Data\EventSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getListByOrderIncrementId($orderIncrementId);

    /**
     * Retrieve Event matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Netresearch\Epayments\Api\Data\EventSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete Event
     *
     * @param \Netresearch\Epayments\Api\Data\EventInterface $event
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(\Netresearch\Epayments\Api\Data\EventInterface $event);

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
