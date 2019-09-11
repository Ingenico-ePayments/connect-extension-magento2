<?php

namespace Ingenico\Connect\Model;

use Magento\Framework\Api\DataObjectHelper;
use Ingenico\Connect\Api\Data\EventInterface;
use Ingenico\Connect\Api\Data\EventInterfaceFactory;
use Ingenico\Connect\Model\Event\DataModel;
use Ingenico\Connect\Model\ResourceModel\Event as EventResource;
use Ingenico\Connect\Model\ResourceModel\Event\Collection;

/**
 * Class Event
 *
 * database representation of an event
 *
 * @package Ingenico\Connect\Model
 */
class Event extends \Magento\Framework\Model\AbstractModel
{

    protected $_eventPrefix = 'ingenico_epayments_event';

    /**
     * @var EventInterfaceFactory
     */
    private $eventDataFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param EventInterfaceFactory $eventDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param EventResource $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
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
