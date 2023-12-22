<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model;

use LogicException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

use function sprintf;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock

class Config implements ConfigInterface
{
    public const CONFIG_INGENICO_CREDIT_CARDS_SAVE_FOR_LATER_VISIBLE = 'payment/worldline_vault/active';

    public const CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT = '0';
    public const CONFIG_INGENICO_CHECKOUT_TYPE_OPTIMIZED_FLOW = '1';
    public const CONFIG_INGENICO_CHECKOUT_TYPE = 'worldline_connect/checkout/inline_payments';
    public const CONFIG_INGENICO_CHECKOUT_TYPE_INLINE = '0';
    public const CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT = '1';
    public const CONFIG_INGENICO_CREDIT_CARDS_PAYMENT_FLOW_TYPE = 'worldline_connect/credit_cards/payment_flow_type';
    public const CONFIG_INGENICO_IDEAL_PAYMENT_FLOW_TYPE = 'worldline_connect/ideal/payment_flow_type';
    public const CONFIG_INGENICO_TRUSTLY_PAYMENT_FLOW_TYPE = 'worldline_connect/trustly/payment_flow_type';
    public const CONFIG_INGENICO_GIROPAY_PAYMENT_FLOW_TYPE = 'worldline_connect/giropay/payment_flow_type';

    public const CONFIG_INGENICO_API_ENDPOINT = 'worldline_connect/settings/api_endpoint';
    public const CONFIG_INGENICO_API_ENDPOINT_SANDBOX = 'sandbox';
    public const CONFIG_INGENICO_API_ENDPOINT_PRE_PROD = 'preprod';
    public const CONFIG_INGENICO_API_ENDPOINT_PROD = 'prod';
    public const CONFIG_INGENICO_API_ENDPOINT_SANDBOX_URL = 'worldline_connect/settings/api_url_sandbox';
    public const CONFIG_INGENICO_API_ENDPOINT_PRE_PROD_URL = 'worldline_connect/settings/api_url_pre_prod';
    public const CONFIG_INGENICO_API_ENDPOINT_PROD_URL = 'worldline_connect/settings/api_url_prod';
    public const CONFIG_INGENICO_WEBHOOKS_KEY_ID_SANDBOX = 'worldline_connect/webhook/webhooks_key_id_sandbox';
    public const CONFIG_INGENICO_WEBHOOKS_KEY_ID_PRE_PROD = 'worldline_connect/webhook/webhooks_key_id_pre_prod';
    public const CONFIG_INGENICO_WEBHOOKS_KEY_ID_PROD = 'worldline_connect/webhook/webhooks_key_id_prod';
    // phpcs:ignore Generic.Files.LineLength.TooLong
    public const CONFIG_INGENICO_WEBHOOKS_SECRET_KEY_SANDBOX =
        'worldline_connect/webhook/webhooks_secret_key_sandbox';
    public const CONFIG_INGENICO_WEBHOOKS_SECRET_KEY_PRE_PROD =
        'worldline_connect/webhook/webhooks_secret_key_pre_prod';
    public const CONFIG_INGENICO_WEBHOOKS_SECRET_KEY_PROD =
        'worldline_connect/webhook/webhooks_secret_key_prod';
    public const CONFIG_INGENICO_API_KEY_SANDBOX = 'worldline_connect/settings/api_key_sandbox';
    public const CONFIG_INGENICO_API_KEY_PRE_PROD = 'worldline_connect/settings/api_key_pre_prod';
    public const CONFIG_INGENICO_API_KEY_PROD = 'worldline_connect/settings/api_key_prod';
    public const CONFIG_INGENICO_API_SECRET_SANDBOX = 'worldline_connect/settings/api_secret_sandbox';
    public const CONFIG_INGENICO_API_SECRET_PRE_PROD = 'worldline_connect/settings/api_secret_pre_prod';
    public const CONFIG_INGENICO_API_SECRET_PROD = 'worldline_connect/settings/api_secret_prod';
    public const CONFIG_INGENICO_MERCHANT_ID_SANDBOX = 'worldline_connect/settings/merchant_id_sandbox';
    public const CONFIG_INGENICO_MERCHANT_ID_PRE_PROD = 'worldline_connect/settings/merchant_id_pre_prod';
    public const CONFIG_INGENICO_MERCHANT_ID_PROD = 'worldline_connect/settings/merchant_id_prod';
    public const CONFIG_INGENICO_FIXED_DESCRIPTOR = 'worldline_connect/settings/descriptor';
    public const CONFIG_INGENICO_HOSTED_CHECKOUT_SUBDOMAIN = 'worldline_connect/settings/hosted_checkout_subdomain';
    public const CONFIG_INGENICO_REDIRECT_TEXT = 'worldline_connect/settings/redirect_text';

    public const CONFIG_INGENICO_LOG_ALL_REQUESTS = 'worldline_connect/settings/log_all_requests';
    public const CONFIG_INGENICO_LOG_ALL_REQUESTS_FILE = 'worldline_connect/settings/log_all_requests_file';
    public const CONFIG_INGENICO_LOG_FRONTEND_REQUESTS = 'worldline_connect/settings/log_frontend_requests';
    public const CONFIG_INGENICO_LIMIT_API_FIELD_LENGTH = 'worldline_connect/settings/limit_api_field_length';
    public const CONFIG_INGENICO_FRAUD_MANAGER_EMAIL = 'worldline_connect/fraud/manager_email';
    public const CONFIG_INGENICO_FRAUD_EMAIL_TEMPLATE = 'worldline_connect/fraud/email_template';

    // phpcs:ignore Generic.Files.LineLength.TooLong
    public const CONFIG_SALES_EMAIL_IDENTITY = 'sales_email/order/identity';
    public const CONFIG_INGENICO_PAYMENT_STATUS = 'worldline_connect/payment_statuses';
    public const CONFIG_INGENICO_REFUND_STATUS = 'worldline_connect/refund_statuses';
    public const CONFIG_INGENICO_HOSTED_CHECKOUT_VARIANT = 'worldline_connect/checkout/hosted_checkout_variant';
    // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
    public const CONFIG_INGENICO_HOSTED_CHECKOUT_GUEST_VARIANT = 'worldline_connect/checkout/hosted_checkout_guest_variant';
    public const CONFIG_INGENICO_HOSTED_CHECKOUT_TITLE = 'payment/worldline_hpp/title';

    /** AdditionalInformation keys */
    public const PAYMENT_ID_KEY = 'worldline_payment_id';
    public const PAYMENT_STATUS_KEY = 'worldline_payment_status';
    public const PAYMENT_STATUS_CODE_KEY = 'worldline_payment_status_code';
    public const PAYMENT_SHOW_DATA_KEY = 'worldline_payment_show_data';
    public const PRODUCT_ID_KEY = 'worldline_payment_product_id';
    public const PRODUCT_LABEL_KEY = 'worldline_payment_product_label';
    public const PRODUCT_PAYMENT_METHOD_KEY = 'worldline_payment_product_method';
    public const PRODUCT_TOKENIZE_KEY = 'worldline_payment_product_tokenize';
    public const CLIENT_PAYLOAD_KEY = 'worldline_payment_payload';
    public const CLIENT_PAYLOAD_IS_PAYMENT_ACCOUNT_ON_FILE = 'worldline_payment_is_payment_account_on_file';
    public const TRANSACTION_RESULTS_KEY = 'worldline_transaction_results';
    public const REDIRECT_URL_KEY = 'worldline_redirect_url';
    public const HOSTED_CHECKOUT_ID_KEY = 'worldline_hosted_checkout_id';
    public const RETURNMAC_KEY = 'worldline_returnmac';
    public const IDEMPOTENCE_KEY = 'worldline_idempotence_key';

    /**
     * @var ScopeConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $scopeConfig;

    /**
     * @var DirectoryList
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $directoryList;

    /**
     * @var EncryptorInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->encryptor = $encryptor;
    }

    /**
     * @param $field
     * @param null $storeId
     * @return mixed
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function getValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue($field, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiKey($storeId = null, $environment = null)
    {
        $environment = $environment !== null ? $environment : $this->getApiEnvironment($storeId);
        switch ($environment) {
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
            sprintf('No Api Key could be found for environment "%s".', $environment)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getApiSecret($storeId = null, $environment = null)
    {
        $environment = $environment !== null ? $environment : $this->getApiEnvironment($storeId);
        switch ($environment) {
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
            sprintf('No Api Secret could be found for environment "%s".', $environment)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantId($storeId = null, $environment = null)
    {
        $environment = $environment !== null ? $environment : $this->getApiEnvironment($storeId);
        switch ($environment) {
            case self::CONFIG_INGENICO_API_ENDPOINT_SANDBOX:
                return $this->getValue(self::CONFIG_INGENICO_MERCHANT_ID_SANDBOX, $storeId);
            case self::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD:
                return $this->getValue(self::CONFIG_INGENICO_MERCHANT_ID_PRE_PROD, $storeId);
            case self::CONFIG_INGENICO_API_ENDPOINT_PROD:
                return $this->getValue(self::CONFIG_INGENICO_MERCHANT_ID_PROD, $storeId);
        }
        throw new LogicException(
            sprintf('No Merchant ID could be found for environment "%s".', $environment)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getApiEndpoint($storeId = null, $environment = null)
    {
        $environment = $environment !== null ? $environment : $this->getApiEnvironment($storeId);
        switch ($environment) {
            case self::CONFIG_INGENICO_API_ENDPOINT_SANDBOX:
                return $this->getValue(self::CONFIG_INGENICO_API_ENDPOINT_SANDBOX_URL, $storeId);
            case self::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD:
                return $this->getValue(self::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD_URL, $storeId);
            case self::CONFIG_INGENICO_API_ENDPOINT_PROD:
                return $this->getValue(self::CONFIG_INGENICO_API_ENDPOINT_PROD_URL, $storeId);
        }
        throw new LogicException(
            sprintf('No API endpoint could be found for environment "%s".', $environment)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getApiEnvironment($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_API_ENDPOINT, $storeId);
    }

    public function getSaveForLaterVisible(int $storeId): bool
    {
        return (bool) $this->getValue(self::CONFIG_INGENICO_CREDIT_CARDS_SAVE_FOR_LATER_VISIBLE);
    }

    public function getRedirectText(int $storeId): string
    {
        return (string) $this->getValue(self::CONFIG_INGENICO_REDIRECT_TEXT);
    }

    /**
     * {@inheritdoc}
     */
    public function getWebHooksKeyId($storeId = null)
    {
        $apiEndpoint = $this->getApiEnvironment($storeId);
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
        $apiEndpoint = $this->getApiEnvironment($storeId);
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

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function getHostedCheckoutTitle($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_HOSTED_CHECKOUT_TITLE, $storeId);
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
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            . DIRECTORY_SEPARATOR
            . $this->getValue(self::CONFIG_INGENICO_LOG_ALL_REQUESTS_FILE, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatusInfo($status, $storeId = null)
    {
        return $this->getValue(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            mb_strtolower(self::CONFIG_INGENICO_REFUND_STATUS . '/' . $status),
            $storeId
        );
    }

    /**
     * @return bool
     */
    public function getLimitAPIFieldLength(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_INGENICO_LIMIT_API_FIELD_LENGTH);
    }
}
