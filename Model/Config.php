<?php

namespace Ingenico\Connect\Model;

use JsonException;
use Ingenico\Connect\Helper\MetaData;
use LogicException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

use function json_decode;
use function sprintf;
use function substr;

class Config implements ConfigInterface
{
    const CONFIG_INGENICO_ACTIVE = 'ingenico_epayments/general/active';

    const CONFIG_INGENICO_PAYMENT_PRODUCT_DISABLED = '0';
    const CONFIG_INGENICO_PAYMENT_PRODUCT_ENABLED = '1';
    const CONFIG_INGENICO_CREDIT_CARDS_TOGGLE = 'ingenico_epayments/credit_cards/toggle';
    const CONFIG_INGENICO_CREDIT_CARDS_SAVE_FOR_LATER_VISIBLE =
        'ingenico_epayments/credit_cards/save_for_later_visible';
    const CONFIG_INGENICO_IDEAL_TOGGLE = 'ingenico_epayments/ideal/toggle';
    const CONFIG_INGENICO_PAYPAL_TOGGLE = 'ingenico_epayments/paypal/toggle';
    const CONFIG_INGENICO_SOFORT_TOGGLE = 'ingenico_epayments/sofort/toggle';
    const CONFIG_INGENICO_TRUSTLY_TOGGLE = 'ingenico_epayments/trustly/toggle';
    const CONFIG_INGENICO_GIROPAY_TOGGLE = 'ingenico_epayments/giropay/toggle';
    const CONFIG_INGENICO_OPEN_BANKING_TOGGLE = 'ingenico_epayments/open_banking/toggle';
    const CONFIG_INGENICO_PAYSAFECARD_TOGGLE = 'ingenico_epayments/paysafecard/toggle';

    const CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT = '0';
    const CONFIG_INGENICO_CHECKOUT_TYPE_OPTIMIZED_FLOW = '1';
    const CONFIG_INGENICO_CHECKOUT_TYPE = 'ingenico_epayments/checkout/inline_payments';
    const CONFIG_INGENICO_CHECKOUT_TYPE_INLINE = '0';
    const CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT = '1';
    const CONFIG_INGENICO_CREDIT_CARDS_PAYMENT_FLOW_TYPE = 'ingenico_epayments/credit_cards/payment_flow_type';
    const CONFIG_INGENICO_IDEAL_PAYMENT_FLOW_TYPE = 'ingenico_epayments/ideal/payment_flow_type';
    const CONFIG_INGENICO_TRUSTLY_PAYMENT_FLOW_TYPE = 'ingenico_epayments/trustly/payment_flow_type';
    const CONFIG_INGENICO_GIROPAY_PAYMENT_FLOW_TYPE = 'ingenico_epayments/giropay/payment_flow_type';

    const CONFIG_INGENICO_CREDIT_CARDS_PRICE_RANGES = 'ingenico_epayments/credit_cards/price_ranges';
    const CONFIG_INGENICO_IDEAL_PRICE_RANGES = 'ingenico_epayments/ideal/price_ranges';
    const CONFIG_INGENICO_PAYPAL_PRICE_RANGES = 'ingenico_epayments/paypal/price_ranges';
    const CONFIG_INGENICO_SOFORT_PRICE_RANGES = 'ingenico_epayments/sofort/price_ranges';
    const CONFIG_INGENICO_TRUSTLY_PRICE_RANGES = 'ingenico_epayments/trustly/price_ranges';
    const CONFIG_INGENICO_GIROPAY_PRICE_RANGES = 'ingenico_epayments/giropay/price_ranges';
    const CONFIG_INGENICO_OPEN_BANKING_PRICE_RANGES = 'ingenico_epayments/open_banking/price_ranges';
    const CONFIG_INGENICO_PAYSAFECARD_PRICE_RANGES = 'ingenico_epayments/paysafecard/price_ranges';

    const CONFIG_INGENICO_CREDIT_CARDS_COUNTRY_BLACKLIST = 'ingenico_epayments/credit_cards/country_blacklist';
    const CONFIG_INGENICO_PAYPAL_COUNTRY_BLACKLIST = 'ingenico_epayments/paypal/country_blacklist';
    const CONFIG_INGENICO_SOFORT_COUNTRY_BLACKLIST = 'ingenico_epayments/sofort/country_blacklist';
    const CONFIG_INGENICO_TRUSTLY_COUNTRY_BLACKLIST = 'ingenico_epayments/trustly/country_blacklist';
    const CONFIG_INGENICO_OPEN_BANKING_COUNTRY_BLACKLIST = 'ingenico_epayments/open_banking/country_blacklist';
    const CONFIG_INGENICO_PAYSAFECARD_COUNTRY_BLACKLIST = 'ingenico_epayments/paysafecard/country_blacklist';

    const CONFIG_INGENICO_API_ENDPOINT = 'ingenico_epayments/settings/api_endpoint';
    const CONFIG_INGENICO_API_ENDPOINT_SANDBOX = 'https://eu.sandbox.api-ingenico.com';
    const CONFIG_INGENICO_API_ENDPOINT_PRE_PROD = 'https://world.preprod.api-ingenico.com';
    const CONFIG_INGENICO_API_ENDPOINT_PROD = 'https://world.api-ingenico.com';
    const CONFIG_INGENICO_WEBHOOKS_KEY_ID_SANDBOX = 'ingenico_epayments/webhook/webhooks_key_id_sandbox';
    const CONFIG_INGENICO_WEBHOOKS_KEY_ID_PRE_PROD = 'ingenico_epayments/webhook/webhooks_key_id_pre_prod';
    const CONFIG_INGENICO_WEBHOOKS_KEY_ID_PROD = 'ingenico_epayments/webhook/webhooks_key_id_prod';
    const CONFIG_INGENICO_WEBHOOKS_SECRET_KEY_SANDBOX = 'ingenico_epayments/webhook/webhooks_secret_key_sandbox';
    const CONFIG_INGENICO_WEBHOOKS_SECRET_KEY_PRE_PROD = 'ingenico_epayments/webhook/webhooks_secret_key_pre_prod';
    const CONFIG_INGENICO_WEBHOOKS_SECRET_KEY_PROD = 'ingenico_epayments/webhook/webhooks_secret_key_prod';
    const CONFIG_INGENICO_API_KEY_SANDBOX = 'ingenico_epayments/settings/api_key_sandbox';
    const CONFIG_INGENICO_API_KEY_PRE_PROD = 'ingenico_epayments/settings/api_key_pre_prod';
    const CONFIG_INGENICO_API_KEY_PROD = 'ingenico_epayments/settings/api_key_prod';
    const CONFIG_INGENICO_API_SECRET_SANDBOX = 'ingenico_epayments/settings/api_secret_sandbox';
    const CONFIG_INGENICO_API_SECRET_PRE_PROD = 'ingenico_epayments/settings/api_secret_pre_prod';
    const CONFIG_INGENICO_API_SECRET_PROD = 'ingenico_epayments/settings/api_secret_prod';
    const CONFIG_INGENICO_MERCHANT_ID_SANDBOX = 'ingenico_epayments/settings/merchant_id_sandbox';
    const CONFIG_INGENICO_MERCHANT_ID_PRE_PROD = 'ingenico_epayments/settings/merchant_id_pre_prod';
    const CONFIG_INGENICO_MERCHANT_ID_PROD = 'ingenico_epayments/settings/merchant_id_prod';
    const CONFIG_INGENICO_FIXED_DESCRIPTOR = 'ingenico_epayments/settings/descriptor';
    const CONFIG_INGENICO_HOSTED_CHECKOUT_SUBDOMAIN = 'ingenico_epayments/settings/hosted_checkout_subdomain';
    // NON-EXISTANT
    const CONFIG_INGENICO_LOG_ALL_REQUESTS = 'ingenico_epayments/settings/log_all_requests';
    const CONFIG_INGENICO_LOG_ALL_REQUESTS_FILE = 'ingenico_epayments/settings/log_all_requests_file'; // NON-EXISTANT
    const CONFIG_INGENICO_LOG_FRONTEND_REQUESTS = 'ingenico_epayments/settings/log_frontend_requests';
    const CONFIG_INGENICO_LIMIT_API_FIELD_LENGTH = 'ingenico_epayments/settings/limit_api_field_length';
    const CONFIG_INGENICO_FRAUD_MANAGER_EMAIL = 'ingenico_epayments/fraud/manager_email';
    const CONFIG_INGENICO_FRAUD_EMAIL_TEMPLATE = 'ingenico_epayments/fraud/email_template'; // NON-EXISTANT
    // phpcs:ignore Generic.Files.LineLength.TooLong
    const CONFIG_INGENICO_CANCELLATION_OF_PENDING_ORDERS = 'ingenico_epayments/order_processing/cancellation_of_pending_orders'; // NO FUNCTION
    const CONFIG_INGENICO_PENDING_ORDERS_DAYS = 'ingenico_epayments/pending_orders_cancellation/days';
    const CONFIG_INGENICO_UPDATE_EMAIL = 'ingenico_epayments/email_settings'; // NON-EXISTANT
    const CONFIG_SALES_EMAIL_IDENTITY = 'sales_email/order/identity'; // NON-EXISTANT
    const CONFIG_INGENICO_PAYMENT_STATUS = 'ingenico_epayments/payment_statuses'; // NON-EXISTANT
    const CONFIG_INGENICO_REFUND_STATUS = 'ingenico_epayments/refund_statuses'; // NON-EXISTANT
    const CONFIG_INGENICO_SYSTEM_PREFIX = 'ingenico_epayments/settings/system_prefix';
    const CONFIG_ALLOW_OFFLINE_REFUNDS = 'ingenico_epayments/settings/allow_offline_refunds';
    const CONFIG_INGENIC_GROUP_CARD_PAYMENT_METHODS = 'ingenico_epayments/credit_cards/group_card_payment_methods';
    const CONFIG_INGENICO_CAPTURES_MODE = 'ingenico_epayments/captures/capture_mode';
    const CONFIG_INGENICO_CAPTURES_MODE_DIRECT = 'direct';
    const CONFIG_INGENICO_CAPTURES_MODE_AUTHORIZE = 'authorize';
    const CONFIG_INGENICO_HOSTED_CHECKOUT_VARIANT = 'ingenico_epayments/checkout/hosted_checkout_variant';
    const CONFIG_INGENICO_HOSTED_CHECKOUT_GUEST_VARIANT = 'ingenico_epayments/checkout/hosted_checkout_guest_variant';
    const CONFIG_INGENICO_HOSTED_CHECKOUT_TITLE = 'payment/ingenico_hpp/title';

    /** AdditionalInformation keys */
    const PAYMENT_ID_KEY = 'ingenico_payment_id';
    const PAYMENT_STATUS_KEY = 'ingenico_payment_status';
    const PAYMENT_STATUS_CODE_KEY = 'ingenico_payment_status_code';
    const PAYMENT_SHOW_DATA_KEY = 'ingenico_payment_show_data';
    const PRODUCT_ID_KEY = 'ingenico_payment_product_id';
    const PRODUCT_LABEL_KEY = 'ingenico_payment_product_label';
    const PRODUCT_PAYMENT_METHOD_KEY = 'ingenico_payment_product_method';
    const PRODUCT_TOKENIZE_KEY = 'ingenico_payment_product_tokenize';
    const CLIENT_PAYLOAD_KEY = 'ingenico_payment_payload';
    const CLIENT_PAYLOAD_IS_PAYMENT_ACCOUNT_ON_FILE = 'ingenico_payment_is_payment_account_on_file';
    const TRANSACTION_RESULTS_KEY = 'ingenico_transaction_results';
    const REDIRECT_URL_KEY = 'ingenico_redirect_url';
    const HOSTED_CHECKOUT_ID_KEY = 'ingenico_hosted_checkout_id';
    const RETURNMAC_KEY = 'ingenico_returnmac';
    const IDEMPOTENCE_KEY = 'ingenico_idempotence_key';

    const CONFIGURABLE_TOGGLE_PAYMENT_PRODUCTS = [
        'cards' => Config::CONFIG_INGENICO_CREDIT_CARDS_TOGGLE,
        806 => Config::CONFIG_INGENICO_TRUSTLY_TOGGLE,
        809 => Config::CONFIG_INGENICO_IDEAL_TOGGLE,
        816 => Config::CONFIG_INGENICO_GIROPAY_TOGGLE,
        830 => Config::CONFIG_INGENICO_PAYSAFECARD_TOGGLE,
        836 => Config::CONFIG_INGENICO_SOFORT_TOGGLE,
        840 => Config::CONFIG_INGENICO_PAYPAL_TOGGLE,
        865 => Config::CONFIG_INGENICO_OPEN_BANKING_TOGGLE,
    ];

    const CONFIGURABLE_PRICE_RANGE_PAYMENT_PRODUCTS = [
        'cards' => Config::CONFIG_INGENICO_CREDIT_CARDS_PRICE_RANGES,
        806 => Config::CONFIG_INGENICO_TRUSTLY_PRICE_RANGES,
        809 => Config::CONFIG_INGENICO_IDEAL_PRICE_RANGES,
        816 => Config::CONFIG_INGENICO_GIROPAY_PRICE_RANGES,
        830 => Config::CONFIG_INGENICO_PAYSAFECARD_PRICE_RANGES,
        836 => Config::CONFIG_INGENICO_SOFORT_PRICE_RANGES,
        840 => Config::CONFIG_INGENICO_PAYPAL_PRICE_RANGES,
        865 => Config::CONFIG_INGENICO_OPEN_BANKING_PRICE_RANGES,
    ];

    const CONFIGURABLE_COUNTRY_BLACKLIST_PAYMENT_PRODUCTS = [
        'cards' => Config::CONFIG_INGENICO_CREDIT_CARDS_COUNTRY_BLACKLIST,
        806 => Config::CONFIG_INGENICO_TRUSTLY_COUNTRY_BLACKLIST,
        830 => Config::CONFIG_INGENICO_PAYSAFECARD_COUNTRY_BLACKLIST,
        836 => Config::CONFIG_INGENICO_SOFORT_COUNTRY_BLACKLIST,
        840 => Config::CONFIG_INGENICO_PAYPAL_COUNTRY_BLACKLIST,
        865 => Config::CONFIG_INGENICO_OPEN_BANKING_COUNTRY_BLACKLIST,
    ];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var MetaData
     */
    private $metaDataHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        EncryptorInterface $encryptor,
        MetaData $metaDataHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->encryptor = $encryptor;
        $this->metaDataHelper = $metaDataHelper;
    }

    /**
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    protected function getValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue($field, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_ACTIVE, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKey($storeId = null)
    {
        $apiEndpoint = $this->getApiEndpoint($storeId);
        switch ($apiEndpoint) {
            case self::CONFIG_INGENICO_API_ENDPOINT_SANDBOX:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_API_KEY_SANDBOX, $storeId)
                );
            case self::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_API_KEY_PRE_PROD, $storeId)
                );
            case self::CONFIG_INGENICO_API_ENDPOINT_PROD:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_API_KEY_PROD, $storeId)
                );
        }
        throw new LogicException(
            sprintf('No Api Key could be found for API Endpoint "%s".', $apiEndpoint)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getApiSecret($storeId = null)
    {
        $apiEndpoint = $this->getApiEndpoint($storeId);
        switch ($apiEndpoint) {
            case self::CONFIG_INGENICO_API_ENDPOINT_SANDBOX:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_API_SECRET_SANDBOX, $storeId)
                );
            case self::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_API_SECRET_PRE_PROD, $storeId)
                );
            case self::CONFIG_INGENICO_API_ENDPOINT_PROD:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_API_SECRET_PROD, $storeId)
                );
        }
        throw new LogicException(
            sprintf('No Api Secret could be found for API Endpoint "%s".', $apiEndpoint)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantId($storeId = null)
    {
        $apiEndpoint = $this->getApiEndpoint($storeId);
        switch ($apiEndpoint) {
            case self::CONFIG_INGENICO_API_ENDPOINT_SANDBOX:
                return $this->getValue(self::CONFIG_INGENICO_MERCHANT_ID_SANDBOX, $storeId);
            case self::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD:
                return $this->getValue(self::CONFIG_INGENICO_MERCHANT_ID_PRE_PROD, $storeId);
            case self::CONFIG_INGENICO_API_ENDPOINT_PROD:
                return $this->getValue(self::CONFIG_INGENICO_MERCHANT_ID_PROD, $storeId);
        }
        throw new LogicException(
            sprintf('No Merchant ID could be found for API Endpoint "%s".', $apiEndpoint)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getApiEndpoint($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_API_ENDPOINT, $storeId);
    }

    /**
     * @param string $configPath
     * @param int|null $storeId
     * @return string
     */
    public function getPaymentProductCheckoutType(string $configPath, ?int $storeId = null)
    {
        return $this->getValue($configPath, $storeId) ===
        self::CONFIG_INGENICO_CHECKOUT_TYPE_INLINE ?
            self::CONFIG_INGENICO_CHECKOUT_TYPE_INLINE :
            self::CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT;
    }

    /**
     * @param string $paymentProductId
     * @param int|null $storeId
     * @return string
     */
    public function isPaymentProductEnabled(string $paymentProductId, ?int $storeId = null)
    {
        if (!array_key_exists($paymentProductId, self::CONFIGURABLE_TOGGLE_PAYMENT_PRODUCTS)) {
            return true;
        }
        return $this->getValue(self::CONFIGURABLE_TOGGLE_PAYMENT_PRODUCTS[$paymentProductId], $storeId) ===
        self::CONFIG_INGENICO_PAYMENT_PRODUCT_ENABLED ?
            self::CONFIG_INGENICO_PAYMENT_PRODUCT_ENABLED :
            self::CONFIG_INGENICO_PAYMENT_PRODUCT_DISABLED;
    }

    /**
     * @inheritDoc
     * @throws JsonException
     */
    public function getPaymentProductPriceRanges(string $paymentProductId, ?int $storeId = null): array
    {
        if (!array_key_exists($paymentProductId, self::CONFIGURABLE_PRICE_RANGE_PAYMENT_PRODUCTS)) {
            return [];
        }
        return $this->formatPaymentProductPriceRanges($paymentProductId, $storeId);
    }

    /**
     * @throws JsonException
     */
    public function isPriceInPaymentProductPriceRange(
        float $orderPrice,
        string $currencyCode,
        string $paymentProductId,
        ?int $storeId = null
    ): bool {
        if (!array_key_exists($paymentProductId, self::CONFIGURABLE_PRICE_RANGE_PAYMENT_PRODUCTS)) {
            return true;
        }
        $productPriceRanges = $this->formatPaymentProductPriceRanges($paymentProductId, $storeId);
        if (empty($productPriceRanges) || !array_key_exists($currencyCode, $productPriceRanges)) {
            return true;
        }
        if ((array_key_exists('min', $productPriceRanges[$currencyCode]) &&
                $productPriceRanges[$currencyCode]['min'] > $orderPrice) ||
            (array_key_exists('max', $productPriceRanges[$currencyCode]) &&
                $productPriceRanges[$currencyCode]['max'] < $orderPrice)
        ) {
            return false;
        }
        return true;
    }

    private function formatPaymentProductPriceRanges(string $paymentProductId, ?int $storeId = null): array
    {
        $formattedPaymentProductPriceRanges = [];
        $paymentProductPriceRanges = json_decode($this->getValue(
            self::CONFIGURABLE_PRICE_RANGE_PAYMENT_PRODUCTS[$paymentProductId],
            $storeId
        ));
        if ($paymentProductPriceRanges === false) {
            throw new JsonException('Unable to decode payment product price range JSON.');
        }
        foreach ((array) $paymentProductPriceRanges as $priceRange) {
            $priceRange = (array) $priceRange;
            $formattedPriceRange = [];
            if (!array_key_exists('minimum', $priceRange) || !array_key_exists('maximum', $priceRange)) {
                throw new JsonException('Payment product price range JSON malformed.');
            }
            if ($priceRange['minimum'] === '' && $priceRange['maximum'] === '') {
                continue;
            }
            if ($priceRange['minimum'] !== '') {
                $formattedPriceRange['min'] = (float) str_replace(',', '.', $priceRange['minimum']);
            }
            if ($priceRange['maximum'] !== '') {
                $formattedPriceRange['max'] = (float) str_replace(',', '.', $priceRange['maximum']);
            }
            $formattedPaymentProductPriceRanges[$priceRange['currency']] = $formattedPriceRange;
        }
        return $formattedPaymentProductPriceRanges;
    }

    public function getPaymentProductCountryRestrictions(string $paymentProductId, ?int $storeId = null): array
    {
        if (!array_key_exists($paymentProductId, self::CONFIGURABLE_COUNTRY_BLACKLIST_PAYMENT_PRODUCTS)) {
            return [];
        }
        return $this->formatPaymentProductCountryRestrictions($paymentProductId, $storeId);
    }

    public function isPaymentProductCountryRestricted(
        string $countryCode,
        string $paymentProductId,
        ?int $storeId = null
    ): bool {
        if (!array_key_exists($paymentProductId, self::CONFIGURABLE_COUNTRY_BLACKLIST_PAYMENT_PRODUCTS)) {
            return false;
        }
        $countryRestrictions = $this->formatPaymentProductCountryRestrictions($paymentProductId, $storeId);
        return in_array($countryCode, $countryRestrictions);
    }

    public function getSaveForLaterVisible(int $storeId): bool
    {
        return (bool) $this->getValue(self::CONFIG_INGENICO_CREDIT_CARDS_SAVE_FOR_LATER_VISIBLE);
    }

    private function formatPaymentProductCountryRestrictions(string $paymentProductId, ?int $storeId = null): array
    {
        $countryRestrictionsString = $this->getValue(
            self::CONFIGURABLE_COUNTRY_BLACKLIST_PAYMENT_PRODUCTS[$paymentProductId],
            $storeId
        );
        if ($countryRestrictionsString === null) {
            return [];
        }
        return explode(',', $countryRestrictionsString);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCheckoutType($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_CHECKOUT_TYPE, $storeId) ===
        self::CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT ?
            self::CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT :
            self::CONFIG_INGENICO_CHECKOUT_TYPE_OPTIMIZED_FLOW;
    }

    /**
     * {@inheritdoc}
     */
    public function getCaptureMode($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_CAPTURES_MODE, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getWebHooksKeyId($storeId = null)
    {
        $apiEndpoint = $this->getApiEndpoint($storeId);
        switch ($apiEndpoint) {
            case self::CONFIG_INGENICO_API_ENDPOINT_SANDBOX:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_WEBHOOKS_KEY_ID_SANDBOX, $storeId)
                );
            case self::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_WEBHOOKS_KEY_ID_PRE_PROD, $storeId)
                );
            case self::CONFIG_INGENICO_API_ENDPOINT_PROD:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_WEBHOOKS_KEY_ID_PROD, $storeId)
                );
        }
        throw new LogicException(
            sprintf('No Webhooks Key ID could be found for API Endpoint "%s".', $apiEndpoint)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWebHooksSecretKey($storeId = null)
    {
        $apiEndpoint = $this->getApiEndpoint($storeId);
        switch ($apiEndpoint) {
            case self::CONFIG_INGENICO_API_ENDPOINT_SANDBOX:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_WEBHOOKS_SECRET_KEY_SANDBOX, $storeId)
                );
            case self::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_WEBHOOKS_SECRET_KEY_PRE_PROD, $storeId)
                );
            case self::CONFIG_INGENICO_API_ENDPOINT_PROD:
                return $this->encryptor->decrypt(
                    $this->getValue(self::CONFIG_INGENICO_WEBHOOKS_SECRET_KEY_PROD, $storeId)
                );
        }
        throw new LogicException(
            sprintf('No Webhooks Secret Key could be found for API Endpoint "%s".', $apiEndpoint)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFraudManagerEmail($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_FRAUD_MANAGER_EMAIL, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getFraudEmailSender($storeId = null)
    {
        return $this->getValue(self::CONFIG_SALES_EMAIL_IDENTITY, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getFraudEmailTemplate($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_FRAUD_EMAIL_TEMPLATE, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescriptor($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_FIXED_DESCRIPTOR, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getHostedCheckoutSubDomain($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_HOSTED_CHECKOUT_SUBDOMAIN, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getHostedCheckoutVariant($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_HOSTED_CHECKOUT_VARIANT, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getHostedCheckoutGuestVariant($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_HOSTED_CHECKOUT_GUEST_VARIANT, $storeId);
    }

    public function getHostedCheckoutTitle($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_HOSTED_CHECKOUT_TITLE, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingOrdersCancellationPeriod($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_PENDING_ORDERS_DAYS, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogAllRequests($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_LOG_ALL_REQUESTS, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogFrontendRequests($storeId = null)
    {
        return (bool) $this->getValue(self::CONFIG_INGENICO_LOG_FRONTEND_REQUESTS, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogAllRequestsFile($storeId = null)
    {
        return $this->directoryList->getPath(DirectoryList::LOG)
            . DIRECTORY_SEPARATOR
            . $this->getValue(self::CONFIG_INGENICO_LOG_ALL_REQUESTS_FILE, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateEmailEnabled($code, $storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_UPDATE_EMAIL . DIRECTORY_SEPARATOR . $code, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateEmailSender($storeId = null)
    {
        return $this->getValue(self::CONFIG_SALES_EMAIL_IDENTITY, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatusInfo($status, $storeId = null)
    {
        return $this->getValue(
            mb_strtolower(self::CONFIG_INGENICO_PAYMENT_STATUS . '/' . $status),
            $storeId
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRefundStatusInfo($status, $storeId = null)
    {
        return $this->getValue(
            mb_strtolower(self::CONFIG_INGENICO_REFUND_STATUS . '/' . $status),
            $storeId
        );
    }

    /**
     * @inheritdoc
     */
    public function getReferencePrefix()
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_INGENICO_SYSTEM_PREFIX,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @inheritDoc
     */
    public function getGroupCardPaymentMethods($storeId = null)
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_INGENIC_GROUP_CARD_PAYMENT_METHODS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @inheritDoc
     */
    public function allowOfflineRefunds(): bool
    {
        return (int) $this->scopeConfig->getValue(self::CONFIG_ALLOW_OFFLINE_REFUNDS) === 1;
    }

    /**
     * @return bool
     */
    public function getLimitAPIFieldLength(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_INGENICO_LIMIT_API_FIELD_LENGTH);
    }
}
