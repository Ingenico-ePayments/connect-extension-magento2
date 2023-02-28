<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline;

use Ingenico\Connect\Sdk\Communicator;

class ClientFactory
{
    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Create class instance with specified parameters
     *
     * @param Communicator $communicator
     * @param string $clientMetaInfo
     * @return \Ingenico\Connect\Sdk\Client
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function create(Communicator $communicator, $clientMetaInfo = '')
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        return new \Ingenico\Connect\Sdk\Client($communicator, $clientMetaInfo);
    }
}
