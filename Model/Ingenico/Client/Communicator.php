<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Client;

use Exception;
use Ingenico\Connect\Sdk\CallContext;
use Ingenico\Connect\Sdk\CommunicatorConfiguration;
use Ingenico\Connect\Sdk\Connection;
use Ingenico\Connect\Sdk\RequestObject;
use Ingenico\Connect\Sdk\ResponseClassMap;
use Psr\Log\LoggerInterface;

class Communicator extends \Ingenico\Connect\Sdk\Communicator
{
    /**
     * @var CommunicatorConfiguration
     */
    private $communicatorConfiguration;

    /**
     * @var LoggerInterface
     */
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
        RequestObject $requestParameters = null,
        CallContext $callContext = null
    ) {
        try {
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
        RequestObject $requestParameters = null,
        CallContext $callContext = null
    ) {
        try {
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
        RequestObject $requestParameters = null,
        CallContext $callContext = null
    ) {
        try {
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
        RequestObject $requestParameters = null,
        CallContext $callContext = null
    ) {
        try {
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
            sprintf(
                'Unable to perform request using communicator configuration: %1$s',
                $exception->getMessage()
            )
        );
    }
}
