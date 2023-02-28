<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Client;

use Exception;
use Ingenico\Connect\Sdk\CallContext;
use Ingenico\Connect\Sdk\CommunicatorConfiguration;
use Ingenico\Connect\Sdk\Connection;
use Ingenico\Connect\Sdk\RequestObject;
use Ingenico\Connect\Sdk\ResponseClassMap;
use Psr\Log\LoggerInterface;

// phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
class Communicator extends \Ingenico\Connect\Sdk\Communicator
{
    /**
     * @var CommunicatorConfiguration
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $communicatorConfiguration;

    /**
     * @var LoggerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    public function __construct(
        Connection $connection,
        CommunicatorConfiguration $communicatorConfiguration,
        LoggerInterface $logger
    ) {
        parent::__construct($connection, $communicatorConfiguration);
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function get(
        ResponseClassMap $responseClassMap,
        $relativeUriPath,
        $clientMetaInfo = '',
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        RequestObject $requestParameters = null,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        CallContext $callContext = null
    ) {
        try {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            return parent::get(...func_get_args());
        } catch (Exception $exception) {
            $this->logEmergency($exception);
        }

        throw $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        ResponseClassMap $responseClassMap,
        $relativeUriPath,
        $clientMetaInfo = '',
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        RequestObject $requestParameters = null,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        CallContext $callContext = null
    ) {
        try {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            return parent::delete(...func_get_args());
        } catch (Exception $exception) {
            $this->logEmergency($exception);
        }

        throw $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function post(
        ResponseClassMap $responseClassMap,
        $relativeUriPath,
        $clientMetaInfo = '',
        $requestBodyObject = null,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        RequestObject $requestParameters = null,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        CallContext $callContext = null
    ) {
        try {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            return parent::post(...func_get_args());
        } catch (Exception $exception) {
            $this->logEmergency($exception);
        }

        throw $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function put(
        ResponseClassMap $responseClassMap,
        $relativeUriPath,
        $clientMetaInfo = '',
        $requestBodyObject = null,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        RequestObject $requestParameters = null,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        CallContext $callContext = null
    ) {
        try {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            return parent::put(...func_get_args());
        } catch (Exception $exception) {
            $this->logEmergency($exception);
        }

        throw $exception;
    }

    /**
     * @param Exception $exception
     */
    private function logEmergency(Exception $exception)
    {
        $this->logger->emergency(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            sprintf(
                'Unable to perform request using communicator configuration: %1$s',
                $exception->getMessage()
            )
        );
    }
}
