<?php

namespace Ingenico\Connect\Model\Ingenico\Client;

use Ingenico\Connect\Sdk\CallContext;
use Ingenico\Connect\Sdk\CommunicatorConfiguration;
use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\InvalidResponseException;
use Ingenico\Connect\Sdk\RequestObject;
use Ingenico\Connect\Sdk\ResponseClassMap;

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
            $result = $this->parentGet(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $requestParameters,
                $callContext
            );
        } catch (InvalidResponseException $e) {
            $this->swapCommunicatorConfiguration();
            $result = $this->parentGet(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $requestParameters,
                $callContext
            );
        } catch (\ErrorException $e) {
            $this->swapCommunicatorConfiguration();
            $result = $this->parentGet(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $requestParameters,
                $callContext
            );
        } catch (\Exception $e) {
            throw $e;
        }

        return $result;
    }

    /**
     * @param $responseClassMap
     * @param $relativeUriPath
     * @param $clientMetaInfo
     * @param $requestParameters
     * @param $callContext
     * @return DataObject
     * @throws \Exception
     */
    protected function parentGet($responseClassMap, $relativeUriPath, $clientMetaInfo, $requestParameters, $callContext)
    {
        return parent::get($responseClassMap, $relativeUriPath, $clientMetaInfo, $requestParameters, $callContext);
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
            $result = $this->parentDelete(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $requestParameters,
                $callContext
            );
        } catch (InvalidResponseException $e) {
            $this->swapCommunicatorConfiguration();
            $result = $this->parentDelete(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $requestParameters,
                $callContext
            );
        } catch (\ErrorException $e) {
            $this->swapCommunicatorConfiguration();
            $result = $this->parentDelete(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $requestParameters,
                $callContext
            );
        } catch (\Exception $e) {
            throw $e;
        }

        return $result;
    }

    /**
     * @param $responseClassMap
     * @param $relativeUriPath
     * @param $clientMetaInfo
     * @param $requestParameters
     * @param $callContext
     * @return DataObject
     * @throws \Exception
     */
    protected function parentDelete(
        $responseClassMap,
        $relativeUriPath,
        $clientMetaInfo,
        $requestParameters,
        $callContext
    ) {
        return parent::delete($responseClassMap, $relativeUriPath, $clientMetaInfo, $requestParameters, $callContext);
    }

    /**
     * {@inheritdoc}
     */
    public function post(
        ResponseClassMap $responseClassMap,
        $relativeUriPath,
        $clientMetaInfo = '',
        DataObject $body = null,
        RequestObject $requestParameters = null,
        CallContext $callContext = null
    ) {
        try {
            $result = $this->parentPost(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $body,
                $requestParameters,
                $callContext
            );
        } catch (InvalidResponseException $e) {
            $this->swapCommunicatorConfiguration();
            $result = $this->parentPost(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $body,
                $requestParameters,
                $callContext
            );
        } catch (\ErrorException $e) {
            $this->swapCommunicatorConfiguration();
            $result = $this->parentPost(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $body,
                $requestParameters,
                $callContext
            );
        } catch (\Exception $e) {
            throw $e;
        }

        return $result;
    }

    /**
     * @param $responseClassMap
     * @param $relativeUriPath
     * @param $clientMetaInfo
     * @param $body
     * @param $requestParameters
     * @param $callContext
     * @return DataObject
     * @throws \Exception
     */
    protected function parentPost(
        $responseClassMap,
        $relativeUriPath,
        $clientMetaInfo,
        $body,
        $requestParameters,
        $callContext
    ) {
        return parent::post(
            $responseClassMap,
            $relativeUriPath,
            $clientMetaInfo,
            $body,
            $requestParameters,
            $callContext
        );
    }

    /**
     * {@inheritdoc}
     */
    public function put(
        ResponseClassMap $responseClassMap,
        $relativeUriPath,
        $clientMetaInfo = '',
        DataObject $body = null,
        RequestObject $requestParameters = null,
        CallContext $callContext = null
    ) {
        try {
            $result = $this->parentPut(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $body,
                $requestParameters,
                $callContext
            );
        } catch (InvalidResponseException $e) {
            $this->swapCommunicatorConfiguration();
            $result = $this->parentPut(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $body,
                $requestParameters,
                $callContext
            );
        } catch (\ErrorException $e) {
            $this->swapCommunicatorConfiguration();
            $result = $this->parentPut(
                $responseClassMap,
                $relativeUriPath,
                $clientMetaInfo,
                $body,
                $requestParameters,
                $callContext
            );
        } catch (\Exception $e) {
            throw $e;
        }

        return $result;
    }

    /**
     * @param $responseClassMap
     * @param $relativeUriPath
     * @param $clientMetaInfo
     * @param $body
     * @param $requestParameters
     * @param $callContext
     * @return DataObject
     * @throws \Exception
     */
    protected function parentPut(
        $responseClassMap,
        $relativeUriPath,
        $clientMetaInfo,
        $body,
        $requestParameters,
        $callContext
    ) {
        return parent::put(
            $responseClassMap,
            $relativeUriPath,
            $clientMetaInfo,
            $body,
            $requestParameters,
            $callContext
        );
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
}
