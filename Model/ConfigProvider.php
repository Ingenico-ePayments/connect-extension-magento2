<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Worldline\Connect\CustomerData\ConnectSession;
use Worldline\Connect\PaymentMethod\PaymentMethods;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse

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
    public const CODE = 'worldline';

    public const CODE_CC_VAULT = 'worldline_cc_vault';

    /**
     * Pattern which is used by payment configuration to fetch data like: title, is_available, sort_order etc
     */
    public const PATH_PATTERN = '%s_epayments/general/%s';

    /**
     * @var UrlInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $urlBuilder;

    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    /**
     * @var Resolver
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $resolver;

    /**
     * @var Repository
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $assetRepo;

    /** @var Session */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $storeManager;

    /**
     * @var ConnectSession
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $connectSession;

    /**
     * @var Data
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentHelper;
    /**
     * @var PaymentMethods
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentMethods;

    /**
     * ConfigProvider constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param ConfigInterface $config
     * @param Resolver $resolver
     * @param Repository $assetRepo
     * @param StoreManagerInterface $storeManager
     * @param ConnectSession $connectSession
     * @param Session $customerSession
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ConfigInterface $config,
        Resolver $resolver,
        Repository $assetRepo,
        StoreManagerInterface $storeManager,
        ConnectSession $connectSession,
        Session $customerSession,
        Data $paymentHelper,
        PaymentMethods $paymentMethods
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
        $this->resolver = $resolver;
        $this->assetRepo = $assetRepo;
        $this->storeManager = $storeManager;
        $this->connectSession = $connectSession;
        $this->customerSession = $customerSession;
        $this->paymentHelper = $paymentHelper;
        $this->paymentMethods = $paymentMethods;
    }

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function getConfig()
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        $products = [];

        foreach ($this->paymentMethods->getPaymentMethods($storeId) as $methodInstance) {
            $productId = $methodInstance->getConfigData('product_id');
            $paymentFlow = $methodInstance->getConfigData('payment_flow');
            $hostedCheckout = $paymentFlow === Config::CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT;
            if ($productId) {
                $products[$methodInstance->getCode()] = [
                    'id' => (int) $methodInstance->getConfigData('product_id'),
                    'hosted' => $hostedCheckout,
                ];
            }

            $productId = $methodInstance->getConfigData('product_group');
            if ($productId) {
                $products[$methodInstance->getCode()] = [
                    'id' => $productId,
                    'hosted' => $hostedCheckout,
                ];
            }
        }

        return [
            'payment' => [
                'worldline' => [
                    'merchantId' => $this->config->getMerchantId($storeId),
                    'hostedCheckoutTitle' => $this->config->getHostedCheckoutTitle($storeId),
                    'hostedCheckoutPageUrl' => $this->urlBuilder->getUrl('epayments/hostedCheckoutPage'),
                    'inlineSuccessUrl' => $this->urlBuilder->getUrl('epayments/inlinePayment'),
                    'locale' => $this->resolver->getLocale(),
                    'loaderImage' => $this->assetRepo->getUrlWithParams('images/loader-2.gif', []),
                    'isCustomerLoggedIn' => $this->customerSession->isLoggedIn(),
                    'connectSession' => $this->connectSession->getSectionData(),
                    'logFrontendRequests' => $this->config->getLogFrontendRequests($storeId),
                    'saveForLaterVisible' => $this->config->getSaveForLaterVisible($storeId),
                    'redirectText' => $this->config->getRedirectText($storeId),
                    'products' => $products,
                    'applePay' => [
                        'buttonLocale' => $this->config->getValue('payment/worldline_apple_pay/button_locale'),
                        'buttonStyle' => $this->config->getValue('payment/worldline_apple_pay/button_style'),
                        'buttonType' => $this->config->getValue('payment/worldline_apple_pay/button_type'),
                    ],
                    'googlePay' => [
                        'merchantId' => $this->config->getValue('payment/worldline_google_pay/merchant_id'),
                        'merchantName' => $this->config->getValue('payment/worldline_google_pay/merchant_name'),
                        'environment' => $this->config->getValue('payment/worldline_google_pay/environment'),
                        'buttonColor' => $this->config->getValue('payment/worldline_google_pay/button_color'),
                        'buttonLocale' => $this->config->getValue('payment/worldline_google_pay/button_locale'),
                        'buttonSizeMode' => $this->config->getValue('payment/worldline_google_pay/button_size_mode'),
                        'buttonType' => $this->config->getValue('payment/worldline_google_pay/button_type'),
                    ],
                ],
            ],
        ];
    }
}
