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
    private $originalCommunicatorConfiguration;

    /**
     * @var CommunicatorConfiguration
     */
    private $secondaryCommunicatorConfiguration;

    /**
     * @var bool
     */
    private $secondaryCommunicatorConfigurationEnabled = false;

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
     * Sets secondary communicator configuration which is used to make a second attempt of call in case if first one
     * was terminated by any reason (i.e. invalid response, runtime error, timeout etc)
     *
     * @param CommunicatorConfiguration $communicatorConfiguration
     */
    public function setSecondaryCommunicatorConfiguration(CommunicatorConfiguration $communicatorConfiguration)
    {
        $this->secondaryCommunicatorConfiguration = $communicatorConfiguration;
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
            $this->logWarning($exception);
        }

        $this->swapCommunicatorConfiguration();

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
            $this->logWarning($exception);
        }

        $this->swapCommunicatorConfiguration();

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
            $this->logWarning($exception);
        }

        $this->swapCommunicatorConfiguration();

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
            $this->logWarning($exception);
        }

        $this->swapCommunicatorConfiguration();

        try {
            return parent::put(...func_get_args());
        } catch (Exception $exception) {
            $this->logEmergency($exception);
        }

        throw $exception;
    }

    /**
     * Changes original communicator configuration to secondary configuration
     */
    protected function swapCommunicatorConfiguration()
    {
        if (!$this->secondaryCommunicatorConfigurationEnabled && $this->secondaryCommunicatorConfiguration) {
            $this->secondaryCommunicatorConfigurationEnabled = true;
            $this->originalCommunicatorConfiguration = $this->getCommunicatorConfiguration();
            $this->setCommunicatorConfiguration($this->secondaryCommunicatorConfiguration);
        } elseif ($this->originalCommunicatorConfiguration) {
            $this->secondaryCommunicatorConfigurationEnabled = false;
            $this->setCommunicatorConfiguration($this->originalCommunicatorConfiguration);
        }
    }

    /**
     * @param Exception $exception
     */
    private function logWarning(Exception $exception)
    {
        $this->logger->warning(
            sprintf(
                'Unable to perform request using primary communicator configuration: %1$s',
                $exception->getMessage()
            )
        );
    }

    /**
     * @param Exception $exception
     */
    private function logEmergency(Exception $exception)
    {
        $this->logger->emergency(
            sprintf(
                'Unable to perform request using secondary communicator configuration: %1$s',
                $exception->getMessage()
            )
        );
    }
}
