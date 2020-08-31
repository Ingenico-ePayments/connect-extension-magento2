<?php

declare(strict_types=1);

namespace Ingenico\Connect\Api;

interface EventManagerInterface
{
    /**
     * @param int $status
     * @return Data\EventSearchResultsInterface
     */
    public function getHostedCheckoutEvents(
        int $status = \Ingenico\Connect\Api\Data\EventInterface::STATUS_NEW
    );

    /**
     * @param int $eventStatus
     * @return string[]
     */
    public function findHostedCheckoutIdsInEvents(
        int $eventStatus = \Ingenico\Connect\Api\Data\EventInterface::STATUS_NEW
    ): array;

    /**
     * @param string $hostedCheckoutId
     * @param int $status
     * @return \Ingenico\Connect\Api\Data\EventSearchResultsInterface
     */
    public function getEventsByHostedCheckoutId(
        string $hostedCheckoutId,
        int $status = \Ingenico\Connect\Api\Data\EventInterface::STATUS_NEW
    );
}
