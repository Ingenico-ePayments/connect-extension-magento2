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
     * @return mixed|string|null
     */
    public function getId()
    {
        return $this->_get(self::ID);
    }

    /**
     * @param string $id
     * @return EventInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
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
