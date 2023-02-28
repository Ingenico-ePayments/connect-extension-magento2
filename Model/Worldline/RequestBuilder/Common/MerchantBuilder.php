<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Merchant;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\MerchantFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Worldline\Connect\Helper\Format;

class MerchantBuilder
{
    /**
     * @var MerchantFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $merchantFactory;

    /**
     * @var StoreManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    /**
     * @var Manager
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $moduleManager;

    /**
     * @var Format
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $format;

    public function __construct(
        MerchantFactory $merchantFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
        Manager $moduleManager,
        Format $format
    ) {
        $this->merchantFactory = $merchantFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->moduleManager = $moduleManager;
        $this->format = $format;
    }

    public function create(OrderInterface $order): Merchant
    {
        $merchant = $this->merchantFactory->create();

        try {
            $store = $this->storeManager->getStore($order->getStoreId());
            if ($store instanceof Store) {
                $merchant->websiteUrl = $this->format->limit($store->getBaseUrl(), 60);
                if ($this->isContactModuleEnabled()) {
                    $merchant->contactWebsiteUrl = $store->getUrl('contact');
                }
            }
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
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
