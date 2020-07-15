<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Client\Communicator;

use Ingenico\Connect\Helper\MetaData;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Sdk\CommunicatorConfiguration;
use Ingenico\Connect\Sdk\CommunicatorConfigurationFactory;
use Ingenico\Connect\Sdk\Domain\MetaData\ShoppingCartExtensionFactory;

class ConfigurationBuilder
{
    /**
     * @var ConfigInterface
     */
    private $config;
    
    /**
     * @var CommunicatorConfigurationFactory
     */
    private $communicatorConfigurationFactory;
    
    /**
     * @var ShoppingCartExtensionFactory
     */
    private $shoppingCartExtensionFactory;
    
    /** @var MetaData */
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
    
    /**
     * @param int $scopeId
     * @param string[] $data
     * @return CommunicatorConfiguration
     */
    public function build(int $scopeId, $data = []): CommunicatorConfiguration
    {
        $cartExtension = $this->shoppingCartExtensionFactory->create([
            'creator' => $this->metaDataHelper->getExtensionCreator(),
            'name' => $this->metaDataHelper->getExtensionName(),
            'version' => $this->metaDataHelper->getExtensionEdition(),
            'extensionId' => $this->metaDataHelper->getModuleVersion(),
        ]);
        
        $apiKey = !empty($data['api_key']) ? $data['api_key'] : $this->config->getApiKey($scopeId);
        $apiSecret = !empty($data['api_secret']) ? $data['api_secret'] : $this->config->getApiSecret($scopeId);
        $apiEndpoint = !empty($data['api_endpoint']) ?
            $data['api_endpoint'] : $this->config->getApiEndpoint($scopeId);
        
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
