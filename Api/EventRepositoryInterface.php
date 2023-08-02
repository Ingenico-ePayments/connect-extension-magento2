<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Api;

use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Interface EventRepositoryInterface
 *
 * @package Worldline\Connect\Api
 */
// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface EventRepositoryInterface
{
    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Save Event
     *
     * @param \Worldline\Connect\Api\Data\EventInterface $event
     * @return \Worldline\Connect\Api\Data\EventInterface
     * @throws CouldNotSaveException
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function save(\Worldline\Connect\Api\Data\EventInterface $event);

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Retrieve Event matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Worldline\Connect\Api\Data\EventSearchResultsInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
