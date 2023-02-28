<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Client\Communicator;

use Ingenico\Connect\Sdk\CommunicatorConfiguration;
use Ingenico\Connect\Sdk\CommunicatorConfigurationFactory;
use Ingenico\Connect\Sdk\Domain\MetaData\ShoppingCartExtensionFactory;
use Worldline\Connect\Helper\MetaData;
use Worldline\Connect\Model\ConfigInterface;

class ConfigurationBuilder
{
    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    /**
     * @var CommunicatorConfigurationFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $communicatorConfigurationFactory;

    /**
     * @var ShoppingCartExtensionFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $shoppingCartExtensionFactory;

    /** @var MetaData */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $metaDataHelper;

    public function __construct(
        CommunicatorConfigurationFactory $communicatorConfigurationFactory,
        ShoppingCartExtensionFactory $shoppingCartExtensionFactory,
        ConfigInterface $config,
        MetaData $metaDataHelper
    ) {
        $this->communicatorConfigurationFactory = $communicatorConfigurationFactory;
        $this->shoppingCartExtensionFactory = $shoppingCartExtensionFactory;
        $this->config = $config;
        $this->metaDataHelper = $metaDataHelper;
    }

    // phpcs:disable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @param int $scopeId
     * @param string[] $data
     * @return CommunicatorConfiguration
     */
    // phpcs:enable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    public function build(int $scopeId, $data = []): CommunicatorConfiguration
    {
        $cartExtension = $this->shoppingCartExtensionFactory->create([
            'creator' => $this->metaDataHelper->getExtensionCreator(),
            'name' => $this->metaDataHelper->getExtensionName(),
            'version' => $this->metaDataHelper->getExtensionEdition(),
            'extensionId' => $this->metaDataHelper->getModuleVersion(),
        ]);

        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        $apiKey = !empty($data['api_key']) ? $data['api_key'] : $this->config->getApiKey($scopeId);
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        $apiSecret = !empty($data['api_secret']) ? $data['api_secret'] : $this->config->getApiSecret($scopeId);
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        $apiEndpoint = !empty($data['api_endpoint']) ? $data['api_endpoint'] : $this->config->getApiEndpoint($scopeId);

        $configuration = $this->communicatorConfigurationFactory->create([
            'apiKeyId' => $apiKey,
            'apiSecret' => $apiSecret,
            'apiEndpoint' => $apiEndpoint,
            'integrator' => $this->metaDataHelper->getExtensionCreator(),
        ]);

        $configuration->setShoppingCartExtension($cartExtension);
        return $configuration;
    }
}
