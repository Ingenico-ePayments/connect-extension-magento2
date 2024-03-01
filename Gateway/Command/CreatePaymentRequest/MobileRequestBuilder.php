<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Gateway\Command\CreatePaymentRequest;

use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequestFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificInputFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\MobilePaymentMethodSpecificInput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\MobilePaymentMethodSpecificInputFactory;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Gateway\Command\CreatePaymentRequestBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\FraudFieldsBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\MerchantBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\OrderBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\MethodSpecificInput\Card\ThreeDSecureBuilder;

class MobileRequestBuilder implements CreatePaymentRequestBuilder
{
    public const TRANSACTION_CHANNEL = 'ECOMMERCE';
    public const UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_FIRST = 'first';
    public const UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_SUBSEQUENT = 'subsequent';
    public const UNSCHEDULED_CARD_ON_FILE_REQUESTOR_CARDHOLDER_INITIATED = 'cardholderInitiated';

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
     * @var CardPaymentMethodSpecificInputFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $mobilePaymentMethodSpecificInputFactory;

    /**
     * @param CreatePaymentRequestFactory $createPaymentRequestFactory
     * @param OrderBuilder $orderBuilder
     * @param MerchantBuilder $merchantBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     * @param MobilePaymentMethodSpecificInputFactory $mobilePaymentMethodSpecificInputFactory
     * @param ThreeDSecureBuilder $threeDSecureBuilder
     */
    public function __construct(
        CreatePaymentRequestFactory $createPaymentRequestFactory,
        OrderBuilder $orderBuilder,
        MerchantBuilder $merchantBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder,
        MobilePaymentMethodSpecificInputFactory $mobilePaymentMethodSpecificInputFactory,
        ThreeDSecureBuilder $threeDSecureBuilder
    ) {
        $this->createPaymentRequestFactory = $createPaymentRequestFactory;
        $this->orderBuilder = $orderBuilder;
        $this->merchantBuilder = $merchantBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
        $this->mobilePaymentMethodSpecificInputFactory = $mobilePaymentMethodSpecificInputFactory;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function build(Payment $payment, bool $requiresApproval): CreatePaymentRequest
    {
        $order = $payment->getOrder();

        $request = $this->createPaymentRequestFactory->create();
        $request->order = $this->orderBuilder->create($order);
        $request->merchant = $this->merchantBuilder->create($order);
        $request->fraudFields = $this->fraudFieldsBuilder->create($order);

        /** @var MobilePaymentMethodSpecificInput $input */
        $input = $this->mobilePaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $payment->getAdditionalInformation('product');
        $input->requiresApproval = $requiresApproval;
        $input->encryptedPaymentData = $payment->getAdditionalInformation('token');

        $request->mobilePaymentMethodSpecificInput = $input;

        return $request;
    }
}
