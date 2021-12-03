<?php

namespace Ingenico\Connect\Model;

interface ConfigInterface
{
    /**
     * Returns boolean for module activation status
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null);

    /**
     * Returns Api Key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiKey($storeId = null);

    /**
     * Returns Api Secret
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiSecret($storeId = null);

    /**
     * Returns Merchant Id
     *
     * @param int|null $storeId
     * @return string
     */
    public function getMerchantId($storeId = null);

    /**
     * Returns Api Endpoint
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiEndpoint($storeId = null);

    /**
     * @param string $configPath
     * @param int|null $storeId
     * @return string
     */
    public function getPaymentProductCheckoutType(string $configPath, ?int $storeId = null);

    /**
     * @param string $paymentProductId
     * @param int|null $storeId
     * @return string
     */
    public function isPaymentProductEnabled(string $paymentProductId, ?int $storeId = null);

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getCheckoutType($storeId = null);

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getCaptureMode($storeId = null);

    /**
     * Returns WebHooks Key Id
     *
     * @param int|null $storeId
     * @return string
     */
    public function getWebHooksKeyId($storeId = null);

    /**
     * Returns WebHooks Secret Key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getWebHooksSecretKey($storeId = null);

    /**
     * Returns Fraud Manager Email
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getFraudManagerEmail($storeId = null);

    /**
     * Returns Fraud Email Sender
     *
     * @param int|null $storeId
     * @return string
     */
    public function getFraudEmailSender($storeId = null);

    /**
     * Returns Fraud Email Template
     *
     * @param int|null $storeId
     * @return string
     */
    public function getFraudEmailTemplate($storeId = null);

    /**
     * Returns Soft Descriptor
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDescriptor($storeId = null);

    /**
     * Returns Hosted Checkout SubDomain
     *
     * @param int|null $storeId
     * @return string
     */
    public function getHostedCheckoutSubDomain($storeId = null);

    /**
     * Return Hosted Checkout Variant
     *
     * @param null|int $storeId
     * @return string
     */
    public function getHostedCheckoutVariant($storeId = null);

    /**
     * Return Hosted Checkout Guest Variant
     *
     * @param null|int $storeId
     * @return string
     */
    public function getHostedCheckoutGuestVariant($storeId = null);

    /**
     * Returns period after which pending orders will be canceled
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPendingOrdersCancellationPeriod($storeId = null);

    /**
     * Returns flag (enable/disable) to log all requests
     *
     * @param int|null $storeId
     * @return bool
     */
    public function getLogAllRequests($storeId = null);

    /**
     * Returns flag (enable/disable) to log all frontend requests
     *
     * @param int|null $storeId
     * @return bool
     */
    public function getLogFrontendRequests($storeId = null);

    /**
     * Returns file name of log file
     *
     * @param int|null $storeId
     * @return string
     */
    public function getLogAllRequestsFile($storeId = null);

    /**
     * Returns Email Sender to be used for update notification emails
     *
     * @param int|null $storeId
     * @return string
     */
    public function getUpdateEmailSender($storeId = null);

    /**
     * Returns flag whether update notification enabled for specific status
     *
     * @param $code
     * @param string $storeId
     * @return bool
     */
    public function getUpdateEmailEnabled($code, $storeId = null);

    /**
     * Returns payment status info
     *
     * @param int|null $storeId
     * @param $status
     * @return string
     */
    public function getPaymentStatusInfo($status, $storeId = null);

    /**
     * Returns refund status info
     *
     * @param $status
     * @param null $storeId
     * @return mixed
     */
    public function getRefundStatusInfo($status, $storeId = null);

    /**
     * @return string
     */
    public function getReferencePrefix();

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function getGroupCardPaymentMethods($storeId = null);

    /**
     * @return bool
     */
    public function allowOfflineRefunds(): bool;

    public function getPaymentProductPriceRanges(string $paymentProductId, ?int $storeId = null): array;

    public function isPriceInPaymentProductPriceRange(
        float $orderPrice,
        string $currencyCode,
        string $paymentProductId,
        ?int $storeId = null
    ): bool;

    public function getPaymentProductCountryRestrictions(string $paymentProductId, ?int $storeId = null): array;

    public function isPaymentProductCountryRestricted(
        string $countryCode,
        string $paymentProductId,
        ?int $storeId = null
    ): bool;
}
