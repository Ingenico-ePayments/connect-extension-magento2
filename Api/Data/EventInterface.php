<?php

namespace Ingenico\Connect\Api\Data;

/**
 * Ingenico Webhooks Event Interface
 *
 * @package Ingenico\Connect\Api\Data
 */
interface EventInterface
{

    const ID = 'id';
    const EVENT_ID = 'event_id';
    const ORDER_INCREMENT_ID = 'order_increment_id';
    const CREATED_TIMESTAMP = 'created_at';
    const PAYLOAD = 'payload';
    const STATUS = 'status';

    const STATUS_NEW = 0;
    /** @deprecated */
    const STATUS_PROCESSING = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAILED = 3;

    /**
     * Get event_id
     *
     * @return string|null
     */
    public function getEventId();

    /**
     * Set event_id
     *
     * @param string $eventId
     * @return \Ingenico\Connect\Api\Data\EventInterface
     */
    public function setEventId($eventId);

    /**
     * Get order increment id
     *
     * @return string|null
     */
    public function getOrderIncrementId();

    /**
     * Set order increment id
     *
     * @param string $orderIncrementId
     * @return \Ingenico\Connect\Api\Data\EventInterface
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Get payload
     *
     * @return string|null
     */
    public function getPayload();

    /**
     * Set payload
     *
     * @param string $payload
     * @return \Ingenico\Connect\Api\Data\EventInterface
     */
    public function setPayload($payload);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param int $status
     * @return \Ingenico\Connect\Api\Data\EventInterface
     */
    public function setStatus($status);

    /**
     * Set creation timestamp
     *
     * @param string $timestamp
     * @return \Ingenico\Connect\Api\Data\EventInterface
     */
    public function setCreatedAt($timestamp);

    /**
     * Event creation timestamp from the platform
     *
     * @return string
     */
    public function getCreatedAt();
}
