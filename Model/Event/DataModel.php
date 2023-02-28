<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Event;

use Worldline\Connect\Api\Data\EventInterface;

/**
 * Class DataModel
 *
 * public data representation of a database event
 *
 * @package Worldline\Connect\Model\Event
 */
// phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
class DataModel extends \Magento\Framework\Api\AbstractSimpleObject implements EventInterface
{
    /**
     * @return string
     */
    public function getEventId()
    {
        return (string) $this->_get(self::EVENT_ID);
    }

    /**
     * @param string $eventId
     * @return EventInterface
     */
    public function setEventId($eventId)
    {
        return $this->setData(self::EVENT_ID, $eventId);
    }

    /**
     * @return string
     */
    public function getOrderIncrementId()
    {
        return (string) $this->_get(self::ORDER_INCREMENT_ID);
    }

    /**
     * @param string $orderIncrementId
     * @return EventInterface
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * @return string|null
     */
    public function getPayload()
    {
        return $this->_get(self::PAYLOAD);
    }

    /**
     * @param string $payload
     * @return EventInterface
     */
    public function setPayload($payload)
    {
        return $this->setData(self::PAYLOAD, $payload);
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int) $this->_get(self::STATUS);
    }

    /**
     * @param int $status
     * @return EventInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @param string $timestamp
     * @return EventInterface
     */
    public function setCreatedAt($timestamp)
    {
        return $this->setData(self::CREATED_TIMESTAMP, $timestamp);
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->_get(self::CREATED_TIMESTAMP);
    }
}
