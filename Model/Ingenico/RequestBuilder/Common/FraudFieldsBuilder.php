<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Sdk\Domain\Definitions\FraudFields;
use Ingenico\Connect\Sdk\Domain\Definitions\FraudFieldsFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

/**
 * Class FraudFieldsBuilder
 */
class FraudFieldsBuilder
{
    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var FraudFieldsFactory
     */
    private $fraudFieldsFactory;

    /**
     * FraudFieldsBuilder constructor.
     *
     * @param RemoteAddress $remoteAddress
     * @param FraudFieldsFactory $fraudFieldsFactory
     */
    public function __construct(
        RemoteAddress $remoteAddress,
        FraudFieldsFactory $fraudFieldsFactory
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->fraudFieldsFactory = $fraudFieldsFactory;
    }

    /**
     * @return FraudFields
     */
    public function create()
    {
        $fraudFields = $this->fraudFieldsFactory->create();
        $fraudFields->customerIpAddress = $this->remoteAddress->getRemoteAddress(false);

        return $fraudFields;
    }
}
