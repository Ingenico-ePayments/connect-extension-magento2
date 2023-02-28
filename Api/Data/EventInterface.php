<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Api\Data;

/**
 * Worldline Webhooks Event Interface
 *
 * @package Worldline\Connect\Api\Data
 */
// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface EventInterface
{
    public const ID = 'id';
    public const EVENT_ID = 'event_id';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const CREATED_TIMESTAMP = 'created_at';
    public const PAYLOAD = 'payload';
    public const STATUS = 'status';

    public const STATUS_NEW = 0;
    /** @deprecated */
    public const STATUS_PROCESSING = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAILED = 3;
    public const STATUS_IGNORED = 4;

    /**
     * Get event_id
     *
     * @return string|null
     */
    public function getEventId();

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Set event_id
     *
     * @param string $eventId
     * @return \Worldline\Connect\Api\Data\EventInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function setEventId($eventId);

    /**
     * Get order increment id
     *
     * @return string|null
     */
    public function getOrderIncrementId();

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Set order increment id
     *
     * @param string $orderIncrementId
     * @return \Worldline\Connect\Api\Data\EventInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Get payload
     *
     * @return string|null
     */
    public function getPayload();

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Set payload
     *
     * @param string $payload
     * @return \Worldline\Connect\Api\Data\EventInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function setPayload($payload);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Set status
     *
     * @param int $status
     * @return \Worldline\Connect\Api\Data\EventInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function setStatus($status);

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Set creation timestamp
     *
     * @param string $timestamp
     * @return \Worldline\Connect\Api\Data\EventInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function setCreatedAt($timestamp);

    /**
     * Event creation timestamp from the platform
     *
     * @return string
     */
    public function getCreatedAt();
}
