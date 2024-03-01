<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Gateway\Command\CreatePaymentRequest;

use Ingenico\Connect\Sdk\Domain\Definitions\FraudFields;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequestFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RedirectPaymentMethodSpecificInputFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Gateway\Command\CreatePaymentRequestBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\FraudFieldsBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\MerchantBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\OrderBuilder;

use function __;

class RedirectRequestBuilder implements CreatePaymentRequestBuilder
{
    public const REDIRECT_PAYMENT_RETURN_URL = 'epayments/inlinePayment/processReturn';
    public const HOSTED_CHECKOUT_RETURN_URL = 'epayments/hostedCheckoutPage/processReturn';

    /**
     * @var CreatePaymentRequestFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $createPaymentRequestFactory;

    /**
     * @var OrderBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderBuilder;

    /**
     * @var MerchantBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $merchantBuilder;

    /**
     * @var FraudFieldsBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $fraudFieldsBuilder;

    /**
     * @var RedirectPaymentMethodSpecificInputFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $redirectTransferPaymentMethodSpecificInputFactory;

    /**
     * @var UrlInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $urlBuilder;

    /**
     * @param CreatePaymentRequestFactory $createPaymentRequestFactory
     * @param OrderBuilder $orderBuilder
     * @param MerchantBuilder $merchantBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     * @param RedirectPaymentMethodSpecificInputFactory $redirectTransferPaymentMethodSpecificInputFactory
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        CreatePaymentRequestFactory $createPaymentRequestFactory,
        OrderBuilder $orderBuilder,
        MerchantBuilder $merchantBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder,
        RedirectPaymentMethodSpecificInputFactory $redirectTransferPaymentMethodSpecificInputFactory,
        UrlInterface $urlBuilder
    ) {
        $this->createPaymentRequestFactory = $createPaymentRequestFactory;
        $this->orderBuilder = $orderBuilder;
        $this->merchantBuilder = $merchantBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
        $this->redirectTransferPaymentMethodSpecificInputFactory = $redirectTransferPaymentMethodSpecificInputFactory;
        $this->urlBuilder = $urlBuilder;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function build(Payment $payment, bool $requiresApproval)
    {
        $order = $payment->getOrder();

        $paymentProductId = $payment->getMethodInstance()->getConfigData('product_id');
        if ($paymentProductId === false) {
            throw new LocalizedException(__('Unknown payment method.'));
        }

        $request = $this->createPaymentRequestFactory->create();
        $request->order = $this->orderBuilder->create($order);
        $request->merchant = $this->merchantBuilder->create($order);
        $request->fraudFields = $this->fraudFieldsBuilder->create($order);
        $request->encryptedCustomerInput = $order->getPayment()->getAdditionalInformation('input');

        $input = $this->redirectTransferPaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $paymentProductId;

        $payload = $order->getPayment()->getAdditionalInformation('input');

        $input->returnUrl = $payload ?
            $this->urlBuilder->getUrl(self::REDIRECT_PAYMENT_RETURN_URL) :
            $this->urlBuilder->getUrl(self::HOSTED_CHECKOUT_RETURN_URL);

        $input->tokenize = false;

        $request->redirectPaymentMethodSpecificInput = $input;

        $payment->setIsTransactionPending(true);

        $request->encryptedCustomerInput = $payload;
        $request->fraudFields = new FraudFields();
        $request->fraudFields->customerIpAddress = $order->getRemoteIp();

        return $request;
    }
}
