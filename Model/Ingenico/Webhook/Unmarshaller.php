<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Webhook;

use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Ingenico\Connect\Sdk\Webhooks\ApiVersionMismatchException;
use Ingenico\Connect\Sdk\Webhooks\InMemorySecretKeyStoreFactory;
use Ingenico\Connect\Sdk\Webhooks\SignatureValidationException;
use Ingenico\Connect\Sdk\Webhooks\WebhooksHelperFactory;
use RuntimeException;

class Unmarshaller
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var InMemorySecretKeyStoreFactory
     */
    private $keyStoreFactory;

    /**
     * @var WebhooksHelperFactory
     */
    private $webhooksHelperFactory;

    public function __construct(
        Config $config,
        InMemorySecretKeyStoreFactory $keyStoreFactory,
        WebhooksHelperFactory $webhooksHelperFactory
    ) {
        $this->config = $config;
        $this->keyStoreFactory = $keyStoreFactory;
        $this->webhooksHelperFactory = $webhooksHelperFactory;
    }

    /**
     * Unmarshal and return value
     *
     * @param string $body
     * @param array $requestHeaders
     * @return WebhooksEvent
     * @throws SignatureValidationException
     * @throws ApiVersionMismatchException
     */
    public function unmarshal($body, array $requestHeaders)
    {
        $secretKeys = $this->resolveSecretKey($requestHeaders);
        $secretKeyStore = $this->keyStoreFactory->create(['secretKeys' => $secretKeys]);
        $helper = $this->webhooksHelperFactory->create(['secretKeyStore' => $secretKeyStore]);

        return $helper->unmarshal($body, $requestHeaders);
    }

    /**
     * Find proper pair key => value
     *
     * @param array $requestHeaders
     * @return array
     */
    private function resolveSecretKey(array $requestHeaders)
    {
        switch ($requestHeaders['X-GCS-KeyId']) {
            case $this->config->getWebhooksKeyId():
                $pattern = [$this->config->getWebhooksKeyId() => $this->config->getWebhooksSecretKey()];
                break;
            default:
                throw new RuntimeException('Secret key not found in Magento settings');
        }

        return $pattern;
    }
}
