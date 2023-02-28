<?php

declare(strict_types=1);

namespace Worldline\Connect\Api;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface EventManagerInterface
{
    /**
     * @param int $status
     * @return Data\EventSearchResultsInterface
     */
    public function getHostedCheckoutEvents(
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        int $status = \Worldline\Connect\Api\Data\EventInterface::STATUS_NEW
    );

    // phpcs:disable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @param int $eventStatus
     * @return string[]
     */
    // phpcs:enable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    public function findHostedCheckoutIdsInEvents(
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        int $eventStatus = \Worldline\Connect\Api\Data\EventInterface::STATUS_NEW
    ): array;

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param string $hostedCheckoutId
     * @param int $status
     * @return \Worldline\Connect\Api\Data\EventSearchResultsInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function getEventsByHostedCheckoutId(
        string $hostedCheckoutId,
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        int $status = \Worldline\Connect\Api\Data\EventInterface::STATUS_NEW
    );
}
