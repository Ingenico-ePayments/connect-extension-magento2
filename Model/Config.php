<?php

namespace Ingenico\Connect\Model;

use Ingenico\Connect\Helper\MetaData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    const CONFIG_INGENICO_ACTIVE = 'ingenico_epayments/general/active';

    const CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT = '0';
    const CONFIG_INGENICO_CHECKOUT_TYPE_INLINE = '1';
    const CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT = '2';
    const CONFIG_INGENICO_CHECKOUT_TYPE = 'ingenico_epayments/checkout/inline_payments';

    const CONFIG_INGENICO_API_ENDPOINT = 'ingenico_epayments/settings/api_endpoint';
    const CONFIG_INGENICO_WEBHOOKS_KEY_ID = 'ingenico_epayments/webhook/webhooks_key_id';
    const CONFIG_INGENICO_WEBHOOKS_SECRET_KEY = 'ingenico_epayments/webhook/webhooks_secret_key';
    const CONFIG_INGENICO_API_KEY = 'ingenico_epayments/settings/api_key';
    const CONFIG_INGENICO_API_SECRET = 'ingenico_epayments/settings/api_secret';
    const CONFIG_INGENICO_MERCHANT_ID = 'ingenico_epayments/settings/merchant_id';
    const CONFIG_INGENICO_FIXED_DESCRIPTOR = 'ingenico_epayments/settings/descriptor';
    const CONFIG_INGENICO_HOSTED_CHECKOUT_SUBDOMAIN = 'ingenico_epayments/settings/hosted_checkout_subdomain';
    const CONFIG_INGENICO_LOG_ALL_REQUESTS = 'ingenico_epayments/settings/log_all_requests';
    const CONFIG_INGENICO_LOG_ALL_REQUESTS_FILE = 'ingenico_epayments/settings/log_all_requests_file';
    const CONFIG_INGENICO_LOG_FRONTEND_REQUESTS = 'ingenico_epayments/settings/log_frontend_requests';
    const CONFIG_INGENICO_FRAUD_MANAGER_EMAIL = 'ingenico_epayments/fraud/manager_email';
    const CONFIG_INGENICO_FRAUD_EMAIL_TEMPLATE = 'ingenico_epayments/fraud/email_template';
    const CONFIG_INGENICO_PENDING_ORDERS_DAYS = 'ingenico_epayments/pending_orders_cancellation/days';
    const CONFIG_INGENICO_UPDATE_EMAIL = 'ingenico_epayments/email_settings';
    const CONFIG_SALES_EMAIL_IDENTITY = 'sales_email/order/identity';
    const CONFIG_INGENICO_PAYMENT_STATUS = 'ingenico_epayments/payment_statuses';
    const CONFIG_INGENICO_REFUND_STATUS = 'ingenico_epayments/refund_statuses';
    const CONFIG_INGENICO_SYSTEM_PREFIX = 'ingenico_epayments/settings/system_prefix';
    const CONFIG_ALLOW_OFFLINE_REFUNDS = 'ingenico_epayments/settings/allow_offline_refunds';

    // phpcs:ignore Generic.Files.LineLength.TooLong
    const CONFIG_INGENIC_GROUP_CARD_PAYMENT_METHODS = 'ingenico_epayments/settings/ux/payment_methods/group_card_payment_methods';

    const CONFIG_INGENICO_CAPTURES_MODE = 'ingenico_epayments/captures/capture_mode';
    const CONFIG_INGENICO_CAPTURES_MODE_DIRECT = 'direct';
    const CONFIG_INGENICO_CAPTURES_MODE_AUTHORIZE = 'authorize';
    const CONFIG_INGENICO_HOSTED_CHECKOUT_VARIANT = 'ingenico_epayments/checkout/hosted_checkout_variant';
    const CONFIG_INGENICO_HOSTED_CHECKOUT_GUEST_VARIANT = 'ingenico_epayments/checkout/hosted_checkout_guest_variant';

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

    /** @var MetaData */
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
    private function getValue($field, $storeId = null)
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
        return $this->encryptor->decrypt($this->getValue(self::CONFIG_INGENICO_API_KEY, $storeId));
    }

    /**
     * {@inheritdoc}
     */
    public function getApiSecret($storeId = null)
    {
        return $this->encryptor->decrypt($this->getValue(self::CONFIG_INGENICO_API_SECRET, $storeId));
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantId($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_MERCHANT_ID, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getApiEndpoint($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_API_ENDPOINT, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCheckoutType($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_CHECKOUT_TYPE, $storeId);
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
        return $this->encryptor->decrypt($this->getValue(self::CONFIG_INGENICO_WEBHOOKS_KEY_ID, $storeId));
    }

    /**
     * {@inheritdoc}
     */
    public function getWebHooksSecretKey($storeId = null)
    {
        return $this->encryptor->decrypt($this->getValue(self::CONFIG_INGENICO_WEBHOOKS_SECRET_KEY, $storeId));
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
}
