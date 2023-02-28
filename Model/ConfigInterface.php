<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
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
     * @param int|null $apiEndpoint
     * @return string
     */
    public function getApiKey($storeId = null, $apiEndpoint = null);

    /**
     * Returns Api Secret
     *
     * @param int|null $storeId
     * @param int|null $apiEndpoint
     * @return string
     */
    public function getApiSecret($storeId = null, $apiEndpoint = null);

    /**
     * Returns Merchant Id
     *
     * @param int|null $storeId
     * @param int|null $apiEndpoint
     * @return string
     */
    public function getMerchantId($storeId = null, $apiEndpoint = null);

    /**
     * Returns Api Endpoint
     *
     * @param int|null $storeId
     * @return string
     */
    public function getApiEndpoint($storeId = null);

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
     * @param null|int $storeId
     * @return string
     */
    public function getHostedCheckoutTitle($storeId = null);

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
     * Returns payment status info
     *
     * @param int|null $storeId
     * @param $status
     * @return string
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function getPaymentStatusInfo($status, $storeId = null);

    /**
     * Returns refund status info
     *
     * @param $status
     * @param null $storeId
     * @return mixed
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function getRefundStatusInfo($status, $storeId = null);

    public function getLimitAPIFieldLength(): bool;

    public function getSaveForLaterVisible(int $storeId): bool;
}
