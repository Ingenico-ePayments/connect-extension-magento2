<?php

namespace Netresearch\Epayments\Model\Ingenico;

use Ingenico\Connect\Sdk\Communicator;

class ClientFactory
{
    /**
     * Create class instance with specified parameters
     *
     * @param Communicator $communicator
     * @param string $clientMetaInfo
     * @return \Ingenico\Connect\Sdk\Client
     */
    public function create(Communicator $communicator, $clientMetaInfo = '')
    {
        return new \Ingenico\Connect\Sdk\Client($communicator, $clientMetaInfo);
    }
}
