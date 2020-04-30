<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\HostedCheckout;

use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\GetHostedCheckoutResponse;

class StatusManagement
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(
        ClientInterface $client,
        ConfigInterface $config
    ) {
        $this->client = $client;
        $this->config = $config;
    }

    public function getStatus(string $hostedCheckoutId): GetHostedCheckoutResponse
    {
        return $this->client->getIngenicoClient()
            ->merchant($this->config->getMerchantId())
            ->hostedcheckouts()
            ->get($hostedCheckoutId);
    }
}
