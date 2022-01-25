<?php

namespace Ingenico\Connect\Model;

use Ingenico\Connect\CustomerData\ConnectSession;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Locking inline payment products in the optimized flow occurs to show a redirect message
     * and to make the payment process more efficient.
     */
    public const LOCKED_INLINE_PAYMENT_PRODUCTS = [
        '830', // PaySafeCard
        '836', // Sofort
        '840', // PayPal
        '865', // Open Banking
    ];

    public const CONFIGURABLE_INLINE_PAYMENT_PRODUCTS = [
        'cards' => Config::CONFIG_INGENICO_CREDIT_CARDS_PAYMENT_FLOW_TYPE,
        806 => Config::CONFIG_INGENICO_TRUSTLY_PAYMENT_FLOW_TYPE,
        809 => Config::CONFIG_INGENICO_IDEAL_PAYMENT_FLOW_TYPE,
        816 => Config::CONFIG_INGENICO_GIROPAY_PAYMENT_FLOW_TYPE,
    ];

    /**
     * Payment Method code which Magento uses as an identifier for particular payment method
     */
    const CODE = 'ingenico';

    const CODE_CC_VAULT = 'ingenico_cc_vault';

    /**
     * Pattern which is used by payment configuration to fetch data like: title, is_available, sort_order etc
     */
    const PATH_PATTERN = '%s_epayments/general/%s';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var Repository
     */
    private $assetRepo;

    /** @var Session */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConnectSession
     */
    private $connectSession;

    /**
     * ConfigProvider constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param ConfigInterface $config
     * @param Resolver $resolver
     * @param Repository $assetRepo
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param ConnectSession $connectSession
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ConfigInterface $config,
        Resolver $resolver,
        Repository $assetRepo,
        StoreManagerInterface $storeManager,
        ConnectSession $connectSession,
        Session $customerSession
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
        $this->resolver = $resolver;
        $this->assetRepo = $assetRepo;
        $this->storeManager = $storeManager;
        $this->connectSession = $connectSession;
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $checkoutType = $this->config->getCheckoutType($storeId);
        return [
            'payment' => [
                'ingenico' => [
                    'hostedCheckoutPageUrl' => $this->urlBuilder->getUrl('epayments/hostedCheckoutPage'),
                    'inlineSuccessUrl' => $this->urlBuilder->getUrl('epayments/inlinePayment'),
                    'locale' => $this->resolver->getLocale(),
                    'groupCardPaymentMethods' => $this->config->getGroupCardPaymentMethods($storeId),
                    'useFullRedirect' => $checkoutType === Config::CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT,
                    'loaderImage' => $this->assetRepo->getUrlWithParams('images/loader-2.gif', []),
                    'isCustomerLoggedIn' => $this->customerSession->isLoggedIn(),
                    'connectSession' => $this->connectSession->getSectionData(),
                    'logFrontendRequests' => $this->config->getLogFrontendRequests($storeId),
                    'inlinePaymentProducts' => $this->getInlinePaymentProducts($storeId),
                    'disabledPaymentProducts' => $this->getDisabledPaymentProducts($storeId),
                    'priceRangedPaymentProducts' => $this->getPriceRangedPaymentProducts($storeId),
                    'countryRestrictedPaymentProducts' => $this->getCountryRestrictedPaymentProducts($storeId),
                    'saveForLaterVisible' => $this->config->getSaveForLaterVisible($storeId),
                ],
            ],
        ];
    }

    /**
     * @return array<string>
     */
    private function getDisabledPaymentProducts(?int $storeId)
    {
        $disabledPaymentProducts = [];
        foreach (Config::CONFIGURABLE_TOGGLE_PAYMENT_PRODUCTS as $productId => $configPath) {
            if ($this->config->isPaymentProductEnabled((string) $productId, $storeId) ===
                Config::CONFIG_INGENICO_PAYMENT_PRODUCT_DISABLED
            ) {
                $disabledPaymentProducts[] = (string) $productId;
            }
        }
        return $disabledPaymentProducts;
    }

    /**
     * @return array<string>
     */
    private function getInlinePaymentProducts(?int $storeId)
    {
        $inlinePaymentProducts = self::LOCKED_INLINE_PAYMENT_PRODUCTS;
        foreach (self::CONFIGURABLE_INLINE_PAYMENT_PRODUCTS as $productId => $configPath) {
            if ($this->config->getPaymentProductCheckoutType($configPath, $storeId) ===
                Config::CONFIG_INGENICO_CHECKOUT_TYPE_INLINE
            ) {
                $inlinePaymentProducts[] = (string) $productId;
            }
        }
        return $inlinePaymentProducts;
    }

    /**
     * @return array
     */
    private function getPriceRangedPaymentProducts(?int $storeId)
    {
        $priceRangedPaymentProducts = [];
        foreach (Config::CONFIGURABLE_PRICE_RANGE_PAYMENT_PRODUCTS as $productId => $configPath) {
            $priceRangedPaymentProducts[(string) $productId] =
                $this->config->getPaymentProductPriceRanges((string) $productId, $storeId);
        }
        return $priceRangedPaymentProducts;
    }

    private function getCountryRestrictedPaymentProducts(?int $storeId)
    {
        $countryRestrictedPaymentProducts = [];
        foreach (Config::CONFIGURABLE_COUNTRY_BLACKLIST_PAYMENT_PRODUCTS as $productId => $configPath) {
            $countryRestrictionsArray = $this->config->getPaymentProductCountryRestrictions(
                (string) $productId,
                $storeId
            );
            if (!empty($countryRestrictionsArray)) {
                $countryRestrictedPaymentProducts[(string) $productId] = $countryRestrictionsArray;
            }
        }
        return $countryRestrictedPaymentProducts;
    }
}
