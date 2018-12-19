<?php

namespace Netresearch\Epayments\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    const CONFIG_INGENICO_VERSION = 'ingenico_epayments/general/version';
    const CONFIG_INGENICO_ACTIVE = 'ingenico_epayments/general/active';

    const CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT = '0';
    const CONFIG_INGENICO_CHECKOUT_TYPE_INLINE = '1';
    const CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT = '2';
    const CONFIG_INGENICO_CHECKOUT_TYPE = 'ingenico_epayments/checkout/inline_payments';

    const CONFIG_INGENICO_API_ENDPOINT = 'ingenico_epayments/settings/api_endpoint';
    const CONFIG_INGENICO_API_ENDPOINT_SECONDARY = 'ingenico_epayments/settings/api_endpoint_secondary';
    const CONFIG_INGENICO_WEBHOOKS_KEY_ID = 'ingenico_epayments/webhook/webhooks_key_id';
    const CONFIG_INGENICO_WEBHOOKS_SECRET_KEY = 'ingenico_epayments/webhook/webhooks_secret_key';
    const CONFIG_INGENICO_WEBHOOKS_KEY_ID2 = 'ingenico_epayments/webhook/webhooks_key_id2';
    const CONFIG_INGENICO_WEBHOOKS_SECRET_KEY2 = 'ingenico_epayments/webhook/webhooks_secret_key2';
    const CONFIG_INGENICO_API_KEY = 'ingenico_epayments/settings/api_key';
    const CONFIG_INGENICO_API_SECRET = 'ingenico_epayments/settings/api_secret';
    const CONFIG_INGENICO_MERCHANT_ID = 'ingenico_epayments/settings/merchant_id';
    const CONFIG_INGENICO_FIXED_DESCRIPTOR = 'ingenico_epayments/settings/descriptor';
    const CONFIG_INGENICO_HOSTED_CHECKOUT_SUBDOMAIN = 'ingenico_epayments/settings/hosted_checkout_subdomain';
    const CONFIG_INGENICO_LOG_ALL_REQUESTS = 'ingenico_epayments/settings/log_all_requests';
    const CONFIG_INGENICO_LOG_ALL_REQUESTS_FILE = 'ingenico_epayments/settings/log_all_requests_file';
    const CONFIG_INGENICO_FRAUD_MANAGER_EMAIL = 'ingenico_epayments/fraud/manager_email';
    const CONFIG_INGENICO_FRAUD_EMAIL_TEMPLATE = 'ingenico_epayments/fraud/email_template';
    const CONFIG_INGENICO_PENDING_ORDERS_DAYS = 'ingenico_epayments/pending_orders_cancellation/days';
    const CONFIG_INGENICO_UPDATE_EMAIL = 'ingenico_epayments/email_settings';
    const CONFIG_SALES_EMAIL_IDENTITY = 'sales_email/order/identity';
    const CONFIG_INGENICO_PAYMENT_STATUS = 'ingenico_epayments/payment_statuses';
    const CONFIG_INGENICO_SFTP_ACTIVE = 'ingenico_epayments/sftp_settings/active';
    const CONFIG_INGENICO_SFTP_HOST = 'ingenico_epayments/sftp_settings/host';
    const CONFIG_INGENICO_SFTP_USERNAME = 'ingenico_epayments/sftp_settings/username';
    const CONFIG_INGENICO_SFTP_PASSWORD = 'ingenico_epayments/sftp_settings/password';
    const CONFIG_INGENICO_SFTP_REMOTE_PATH = 'ingenico_epayments/sftp_settings/remote_path';
    const CONFIG_INGENICO_SYSTEM_PREFIX = 'ingenico_epayments/settings/system_prefix';

    const CONFIG_INGENICO_METHODS_TOKEN = 'ingenico_epayments/payment_method_groups/TOKEN';
    const CONFIG_INGENICO_METHODS_BANKTRANSFER = 'ingenico_epayments/payment_method_groups/BANKTRANSFER';
    const CONFIG_INGENICO_METHODS_CARD = 'ingenico_epayments/payment_method_groups/CARD';
    const CONFIG_INGENICO_METHODS_CASH = 'ingenico_epayments/payment_method_groups/CASH';
    const CONFIG_INGENICO_METHODS_DIRECTDEBIT = 'ingenico_epayments/payment_method_groups/DIRECTDEBIT';
    const CONFIG_INGENICO_METHODS_EINVOICE = 'ingenico_epayments/payment_method_groups/EINVOICE';
    const CONFIG_INGENICO_METHODS_INVOICE = 'ingenico_epayments/payment_method_groups/INVOICE';
    const CONFIG_INGENICO_METHODS_REDIRECT = 'ingenico_epayments/payment_method_groups/REDIRECT';

    const CONFIG_INGENICO_CAPTURES_MODE = 'ingenico_epayments/captures/capture_mode';
    const CONFIG_INGENICO_CAPTURES_MODE_DIRECT = 'direct';
    const CONFIG_INGENICO_CAPTURES_MODE_AUTHORIZE = 'authorize';
    const CONFIG_INGENICO_HOSTED_CHECKOUT_VARIANT = 'ingenico_epayments/checkout/hosted_checkout_variant';

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
    const TRANSACTION_RESULTS_KEY = 'ingenico_transaction_results';
    const REDIRECT_URL_KEY = 'ingenico_redirect_url';
    const HOSTED_CHECKOUT_ID_KEY = 'ingenico_hosted_checkout_id';
    const RETURNMAC_KEY = 'ingenico_returnmac';
    const IDEMPOTENCE_KEY = 'ingenico_idempotence_key';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /** @var DirectoryList */
    private $directoryList;

    /** @var EncryptorInterface */
    private $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $directoryList
     * @param EncryptorInterface $encryptor
     */
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
    private function getValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue($field, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_VERSION, $storeId);
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
     * {@inheritdoc}
     */
    public function getSecondaryApiEndpoint($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_API_ENDPOINT_SECONDARY, $storeId);
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
    public function getWebHooksKeyId2($storeId = null)
    {
        return $this->encryptor->decrypt($this->getValue(self::CONFIG_INGENICO_WEBHOOKS_KEY_ID2, $storeId));
    }

    /**
     * {@inheritdoc}
     */
    public function getWebHooksSecretKey2($storeId = null)
    {
        return $this->encryptor->decrypt($this->getValue(self::CONFIG_INGENICO_WEBHOOKS_SECRET_KEY2, $storeId));
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
    public function getShoppingCartExtensionName()
    {
        return 'Ingenico Connect Magento 2 Extension';
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegrator()
    {
        return 'Netresearch GmbH & Co. KG';
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getProductGroupTitles($storeId = null)
    {
        return [
            'token' => $this->getValue(self::CONFIG_INGENICO_METHODS_TOKEN, $storeId),
            'bankTransfer' => $this->getValue(self::CONFIG_INGENICO_METHODS_BANKTRANSFER, $storeId),
            'card' => $this->getValue(self::CONFIG_INGENICO_METHODS_CARD, $storeId),
            'cash' => $this->getValue(self::CONFIG_INGENICO_METHODS_CASH, $storeId),
            'directDebit' => $this->getValue(self::CONFIG_INGENICO_METHODS_DIRECTDEBIT, $storeId),
            'eInvoice' => $this->getValue(self::CONFIG_INGENICO_METHODS_EINVOICE, $storeId),
            'invoice' => $this->getValue(self::CONFIG_INGENICO_METHODS_INVOICE, $storeId),
            'redirect' => $this->getValue(self::CONFIG_INGENICO_METHODS_REDIRECT, $storeId),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatusInfo($status, $storeId = null)
    {
        return $this->getValue(
            mb_strtolower(self::CONFIG_INGENICO_PAYMENT_STATUS . DIRECTORY_SEPARATOR . $status),
            $storeId
        );
    }

    /**
     * (@inheritDoc}
     */
    public function getSftpActive($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_SFTP_ACTIVE, $storeId);
    }

    /**
     * (@inheritDoc}
     */
    public function getSftpHost($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_SFTP_HOST, $storeId);
    }

    /**
     * (@inheritDoc}
     */
    public function getSftpUsername($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_SFTP_USERNAME, $storeId);
    }

    /**
     * (@inheritDoc}
     */
    public function getSftpPassword($storeId = null)
    {
        return $this->encryptor->decrypt($this->getValue(self::CONFIG_INGENICO_SFTP_PASSWORD, $storeId));
    }

    /**
     * (@inheritDoc}
     */
    public function getSftpRemotePath($storeId = null)
    {
        return $this->getValue(self::CONFIG_INGENICO_SFTP_REMOTE_PATH, $storeId);
    }

    /***
     * @inheritdoc
     */
    public function getReferencePrefix()
    {
        return (string) $this->getValue(self::CONFIG_INGENICO_SYSTEM_PREFIX);
    }
}
