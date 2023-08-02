<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Event;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Reflection\DataObjectProcessor;
use Worldline\Connect\Api\Data\EventInterface;
use Worldline\Connect\Api\Data\EventSearchResultsInterface;
use Worldline\Connect\Api\Data\EventSearchResultsInterfaceFactory;
use Worldline\Connect\Api\EventRepositoryInterface;
use Worldline\Connect\Model\Event;
use Worldline\Connect\Model\EventFactory;
use Worldline\Connect\Model\ResourceModel\Event as ResourceEvent;
use Worldline\Connect\Model\ResourceModel\Event\Collection;
use Worldline\Connect\Model\ResourceModel\Event\CollectionFactory as EventCollectionFactory;

/**
 * Class EventRepository
 *
 * @package Worldline\Connect\Model\Event
 */
class EventRepository implements EventRepositoryInterface
{
    /**
     * @var ResourceEvent
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $resource;

    /**
     * @var EventFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $eventFactory;

    /**
     * @var EventCollectionFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $eventCollectionFactory;

    /**
     * @var EventSearchResultsInterfaceFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $searchResultsFactory;

    /**
     * @var DataObjectProcessor
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $dataObjectProcessor;

    /**
     * @var SearchCriteriaBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $criteriaBuilder;

    /**
     * EventRepository constructor.
     *
     * @param ResourceEvent $resource
     * @param EventFactory $eventFactory
     * @param EventCollectionFactory $eventCollectionFactory
     * @param EventSearchResultsInterfaceFactory $searchResultsFactory
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
        $eventModel->addData($eventData);

        try {
            $this->resource->save($eventModel);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __(
                    'Could not save the event: %1',
                    $exception->getMessage()
                )
            );
        }

        return $eventModel->getDataModel();
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
                // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
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

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param Collection $collection
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    private function addFilterGroupToCollection(
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
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
