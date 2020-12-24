<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Sdk\Domain\Definitions\FraudFields;
use Ingenico\Connect\Sdk\Domain\Definitions\FraudFieldsFactory;
use Magento\Sales\Api\Data\OrderInterface;

class FraudFieldsBuilder
{
    /**
     * @var FraudFieldsFactory
     */
    private $fraudFieldsFactory;

    public function __construct(FraudFieldsFactory $fraudFieldsFactory)
    {
        $this->fraudFieldsFactory = $fraudFieldsFactory;
    }

    public function create(OrderInterface $order): FraudFields
    {
        $fraudFields = $this->fraudFieldsFactory->create();
        $fraudFields->customerIpAddress = $order->getRemoteIp();

        return $fraudFields;
    }
}
