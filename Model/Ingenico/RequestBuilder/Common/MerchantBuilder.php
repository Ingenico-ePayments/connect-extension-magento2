<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Merchant;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\MerchantFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class MerchantBuilder
{
    /**
     * @var MerchantFactory
     */
    private $merchantFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        MerchantFactory $merchantFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        Manager $moduleManager
    ) {
        $this->merchantFactory = $merchantFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->moduleManager = $moduleManager;
    }

    public function create(OrderInterface $order): Merchant
    {
        $merchant = $this->merchantFactory->create();

        try {
            $store = $this->storeManager->getStore($order->getStoreId());
            if ($store instanceof Store) {
                $merchant->websiteUrl = $store->getBaseUrl();
                if ($this->isContactModuleEnabled()) {
                    $merchant->contactWebsiteUrl = $store->getUrl('contact');
                }
            }
        } catch (NoSuchEntityException $exception) {
            // Do nothing
        }

        return $merchant;
    }

    private function isContactModuleEnabled(): bool
    {
        // Check if module is installed:
        if (!$this->moduleManager->isEnabled('Magento_Contact')) {
            return false;
        }

        return (int) $this->config->getValue('contact/contact/enabled') === 1;
    }
}
