<?php

namespace Netresearch\Epayments\Model\Ingenico\Webhooks;

use Netresearch\Epayments\Model\Config;

class HelperAdapter
{
    /** @var Config */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Unmarshal and return value
     *
     * @param string $body
     * @param array $requestHeaders
     * @return \Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent
     * @throws \Ingenico\Connect\Sdk\Webhooks\SignatureValidationException
     * @throws \Ingenico\Connect\Sdk\Webhooks\ApiVersionMismatchException
     */
    public function unmarshal($body, array $requestHeaders)
    {
        $secretKeys = $this->resolveSecretKey($requestHeaders);
        $secretKeyStore = new \Ingenico\Connect\Sdk\Webhooks\InMemorySecretKeyStore($secretKeys);
        $helper = new \Ingenico\Connect\Sdk\Webhooks\WebhooksHelper($secretKeyStore);

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
            case $this->config->getWebhooksKeyId2():
                $pattern = [$this->config->getWebhooksKeyId2() => $this->config->getWebhooksSecretKey2()];
                break;
            default:
                throw new \RuntimeException('Secret key not found in Magento settings');
        }

        return $pattern;
    }
}
