<?php

namespace Ingenico\Connect\Model\Ingenico\Client;

use Ingenico\Connect\Sdk\CommunicatorConfiguration;

class CommunicatorConfigurationFactory
{
    /**
     * Create instance of Ingenico CommunicatorConfiguration
     *
     * @param string $apiKeyId
     * @param string $apiSecret
     * @param string $apiEndpoint
     * @param string $integrator
     * @return CommunicatorConfiguration
     */
    public function create($apiKeyId, $apiSecret, $apiEndpoint, $integrator)
    {
        return new CommunicatorConfiguration($apiKeyId, $apiSecret, $apiEndpoint, $integrator);
    }
}
