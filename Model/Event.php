<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model;

use Magento\Framework\Api\DataObjectHelper;
use Worldline\Connect\Api\Data\EventInterface;
use Worldline\Connect\Api\Data\EventInterfaceFactory;
use Worldline\Connect\Model\Event\DataModel;
use Worldline\Connect\Model\ResourceModel\Event as EventResource;
use Worldline\Connect\Model\ResourceModel\Event\Collection;

/**
 * Class Event
 *
 * database representation of an event
 *
 * @package Worldline\Connect\Model
 */
// phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
class Event extends \Magento\Framework\Model\AbstractModel
{
    // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore, SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
    protected $_eventPrefix = 'worldline_connect_event';

    /**
     * @var EventInterfaceFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $eventDataFactory;

    /**
     * @var DataObjectHelper
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $dataObjectHelper;

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param EventInterfaceFactory $eventDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param EventResource $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function __construct(
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        \Magento\Framework\Model\Context $context,
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        \Magento\Framework\Registry $registry,
        EventInterfaceFactory $eventDataFactory,
        DataObjectHelper $dataObjectHelper,
        EventResource $resource,
        Collection $resourceCollection,
        array $data = []
    ) {
        $this->eventDataFactory = $eventDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve event model with event data
     *
     * @return EventInterface
     */
    public function getDataModel()
    {
        $eventData = $this->getData();

        /** @var DataModel $eventDataObject */
        $eventDataObject = $this->eventDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $eventDataObject,
            $eventData,
            EventInterface::class
        );

        return $eventDataObject;
    }
}
