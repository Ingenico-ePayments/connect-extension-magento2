<?php

namespace Ingenico\Connect\Model\Event;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Reflection\DataObjectProcessor;
use Ingenico\Connect\Api\Data\EventInterface;
use Ingenico\Connect\Api\Data\EventSearchResultsInterface;
use Ingenico\Connect\Api\Data\EventSearchResultsInterfaceFactory;
use Ingenico\Connect\Api\EventRepositoryInterface;
use Ingenico\Connect\Model\Event;
use Ingenico\Connect\Model\EventFactory;
use Ingenico\Connect\Model\ResourceModel\Event as ResourceEvent;
use Ingenico\Connect\Model\ResourceModel\Event\CollectionFactory as EventCollectionFactory;
use Ingenico\Connect\Model\ResourceModel\Event\Collection;

/**
 * Class EventRepository
 *
 * @package Ingenico\Connect\Model\Event
 */
class EventRepository implements EventRepositoryInterface
{

    /**
     * @var ResourceEvent
     */
    private $resource;

    /**
     * @var EventFactory
     */
    private $eventFactory;

    /**
     * @var EventCollectionFactory
     */
    private $eventCollectionFactory;

    /**
     * @var EventSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * EventRepository constructor.
     *
     * @param ResourceEvent $resource
     * @param EventFactory $eventFactory
     * @param EventCollectionFactory $eventCollectionFactory
     * @param EventSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param DataObjectProcessor $dataObjectProcessor
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        ResourceEvent $resource,
        EventFactory $eventFactory,
        EventCollectionFactory $eventCollectionFactory,
        EventSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectProcessor $dataObjectProcessor,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->resource = $resource;
        $this->eventFactory = $eventFactory;
        $this->eventCollectionFactory = $eventCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * @param EventInterface $event
     * @return EventInterface
     * @throws CouldNotSaveException
     */
    public function save(EventInterface $event)
    {
        $eventData = $this->dataObjectProcessor->buildOutputDataArray($event, EventInterface::class);

        /** @var Event|AbstractModel $eventModel */
        $eventModel = $this->eventFactory->create();
        $this->resource->load($eventModel, $event->getEventId(), EventInterface::EVENT_ID);
        $eventModel->addData($eventData);

        try {
            $this->resource->save($eventModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the event: %1',
                    $exception->getMessage()
                )
            );
        }

        return $eventModel->getDataModel();
    }

    /**
     * @param string $orderIncrementId
     * @return EventSearchResultsInterface
     */
    public function getListByOrderIncrementId($orderIncrementId)
    {
        $this->criteriaBuilder->addFilter(EventInterface::ORDER_INCREMENT_ID, $orderIncrementId);

        return $this->getList($this->criteriaBuilder->create());
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return EventSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var Collection $collection */
        $collection = $this->eventCollectionFactory->create();
        $collection->setOrder(EventInterface::CREATED_TIMESTAMP, Collection::SORT_ORDER_ASC);

        //Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }

        /** @var SortOrder $sortOrder */
        foreach ((array) $searchCriteria->getSortOrders() as $sortOrder) {
            $field = $sortOrder->getField();
            $collection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
            );
        }

        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->load();

        $items = [];
        /** @var Event $model */
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        /** @var EventSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @param string $eventId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($eventId)
    {
        return $this->delete($this->getByEventId($eventId));
    }

    /**
     * @param EventInterface $event
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(EventInterface $event)
    {
        try {
            $eventModel = $this->eventFactory->create();
            $this->resource->load($eventModel, $event->getEventId(), EventInterface::EVENT_ID);
            $this->resource->delete($eventModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the Event: %1',
                    $exception->getMessage()
                )
            );
        }

        return true;
    }

    /**
     * @param string $eventId
     * @return EventInterface
     * @throws NoSuchEntityException
     */
    public function getByEventId($eventId)
    {
        /** @var Event $event */
        $event = $this->eventFactory->create();
        $this->resource->load($event, $eventId, EventInterface::EVENT_ID);
        if (!$event->getId()) {
            throw new NoSuchEntityException(__('Event with id "%1" does not exist.', $eventId));
        }

        return $event->getDataModel();
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param Collection $collection
     */
    private function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        Collection $collection
    ) {
        $fields = [];
        $conditions = [];
        foreach ($filterGroup->getFilters() as $filter) {
            $condition = $filter->getConditionType() ?: 'eq';
            $fields[] = $filter->getField();
            $conditions[] = [$condition => $filter->getValue()];
        }

        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
    }
}
