<?php

namespace Netresearch\Epayments\Model\Ingenico\Api;

use Ingenico\Connect\Sdk\CallContext;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutResponse;
use Ingenico\Connect\Sdk\Domain\Payment\ApprovePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CancelApprovalPaymentResponse;
use Ingenico\Connect\Sdk\Domain\Payment\CancelPaymentResponse;
use Ingenico\Connect\Sdk\Domain\Payment\CapturePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentResponse;
use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\Domain\Product\PaymentProducts;
use Ingenico\Connect\Sdk\Domain\Refund\ApproveRefundRequest;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Ingenico\Connect\Sdk\Domain\Refund\RefundResponse;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionRequest;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionResponse;
use Magento\Framework\Exception\LocalizedException;
use Netresearch\Epayments\Model\Ingenico\RequestInterface;

interface ClientInterface
{
    /**
     * Returns initialized Ingenico API client
     *
     * @param null|int $scopeId
     * @return \Ingenico\Connect\Sdk\Client
     */
    public function getIngenicoClient($scopeId = null);

    /**
     * Returns available (configured on Ingenico side) payment products for configured merchant.
     * The result set of products depends on the criteria parameters: currency code, country code and locale
     *
     * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__products_get
     *
     * @param int $amount
     * @param string $currencyCode
     * @param string $countryCode
     * @param string $locale
     * @param int $scopeId
     * @return PaymentProducts
     */
    public function getAvailablePaymentProducts($amount, $currencyCode, $countryCode, $locale, $scopeId);

    /**
     * Return selected payment product
     *
     * @param $paymentProductId
     * @param int $amount
     * @param $currencyCode
     * @param $countryCode
     * @param $locale
     * @param $scopeId
     * @return PaymentProductResponse
     * @throws LocalizedException
     */
    public function getIngenicoPaymentProduct(
        $paymentProductId,
        $amount,
        $currencyCode,
        $countryCode,
        $locale,
        $scopeId
    );

    /**
     * Return response of created hosted checkout
     *
     * @param CreateHostedCheckoutRequest $paymentProductRequest
     * @param int|null $scopeId
     * @return CreateHostedCheckoutResponse
     */
    public function createHostedCheckout(
        CreateHostedCheckoutRequest $paymentProductRequest,
        $scopeId = null
    );

    /**
     * @param CreatePaymentRequest $request
     * @param int|null $scopeId
     * @return CreatePaymentResponse
     */
    public function createPayment(CreatePaymentRequest $request, $scopeId = null);

    /**
     * Return ingenico payment refund
     *
     * @param string $refundId
     * @param int $scopeId
     * @return RefundResponse
     */
    public function ingenicoPaymentRefund($refundId, $scopeId = null);

    /**
     * Return ingenico payment cancel
     *
     * @param string $ingenicoPaymentId
     * @param int $scopeId
     * @return CancelPaymentResponse
     */
    public function ingenicoPaymentCancel($ingenicoPaymentId, $scopeId = null);

    /**
     * Accept ingenico payment
     *
     * @param string $ingenicoPaymentId
     * @param int $scopeId
     * @return PaymentResponse
     */
    public function ingenicoPaymentAccept($ingenicoPaymentId, $scopeId = null);

    /**
     * Approve refund
     *
     * @param string $refundId
     * @param ApproveRefundRequest $request
     * @param int $scopeId
     * @return null
     */
    public function ingenicoRefundAccept($refundId, ApproveRefundRequest $request, $scopeId = null);

    /**
     * Cancel refund
     *
     * @param string $refundId
     * @param int $scopeId
     */
    public function ingenicoRefundCancel($refundId, $scopeId = null);

    /**
     * Return ingenico cancel approval response
     *
     * @param string $ingenicoPaymentId
     * @param int $scopeId
     * @return CancelApprovalPaymentResponse
     */
    public function ingenicoCancelApproval($ingenicoPaymentId, $scopeId = null);

    /**
     * Return ingenico payment
     *
     * @param string $ingenicoPaymentId
     * @param int $scopeId
     * @return PaymentResponse
     */
    public function ingenicoPayment($ingenicoPaymentId, $scopeId = null);

    /**
     * Return ingenico payment
     *
     * @param string $ingenicoPaymentId
     * @param RefundRequest $request
     * @param CallContext $callContext
     * @param int $scopeId
     * @return RefundResponse
     */
    public function ingenicoRefund($ingenicoPaymentId, RefundRequest $request, $callContext, $scopeId = null);

    /**
     * @param string $ingenicoPaymentId
     * @param CapturePaymentRequest $request
     * @param int|null $scopeId
     * @return \Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse
     */
    public function ingenicoPaymentCapture($ingenicoPaymentId, CapturePaymentRequest $request, $scopeId = null);

    /**
     * @param string $ingenicoPaymentId
     * @param ApprovePaymentRequest $request
     * @param int|null $scopeId
     * @return \Ingenico\Connect\Sdk\Domain\Payment\PaymentApprovalResponse
     */
    public function ingenicoPaymentApprove($ingenicoPaymentId, ApprovePaymentRequest $request, $scopeId = null);

    /**
     * @param SessionRequest $request
     * @param int|null $scopeId
     * @return SessionResponse
     */
    public function ingenicoCreateSession(SessionRequest $request, $scopeId = null);

    /**
     * @param int|null $scopeId
     * @param string[] $data
     * @return \Ingenico\Connect\Sdk\Domain\Services\TestConnection
     */
    public function ingenicoTestAccount($scopeId, $data = []);
}
