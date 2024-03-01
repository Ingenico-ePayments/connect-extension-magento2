<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Gateway\Command\CreatePaymentRequest;

use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequestFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificInput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificInputFactory;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Gateway\Command\CreatePaymentRequestBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\FraudFieldsBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\MerchantBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\OrderBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\MethodSpecificInput\Card\ThreeDSecureBuilder;

class CardRequestBuilder implements CreatePaymentRequestBuilder
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
    private $cardPaymentMethodSpecificInputFactory;

    /**
     * @var ThreeDSecureBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $threeDSecureBuilder;

    /**
     * @param CreatePaymentRequestFactory $createPaymentRequestFactory
     * @param OrderBuilder $orderBuilder
     * @param MerchantBuilder $merchantBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     * @param CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory
     * @param ThreeDSecureBuilder $threeDSecureBuilder
     */
    public function __construct(
        CreatePaymentRequestFactory $createPaymentRequestFactory,
        OrderBuilder $orderBuilder,
        MerchantBuilder $merchantBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        ThreeDSecureBuilder $threeDSecureBuilder
    ) {
        $this->createPaymentRequestFactory = $createPaymentRequestFactory;
        $this->orderBuilder = $orderBuilder;
        $this->merchantBuilder = $merchantBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->threeDSecureBuilder = $threeDSecureBuilder;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function build(Payment $payment, bool $requiresApproval): CreatePaymentRequest
    {
        $order = $payment->getOrder();

        $request = $this->createPaymentRequestFactory->create();
        $request->order = $this->orderBuilder->create($order);
        $request->merchant = $this->merchantBuilder->create($order);
        $request->fraudFields = $this->fraudFieldsBuilder->create($order);
        $request->encryptedCustomerInput = $payment->getAdditionalInformation('input');

        /** @var CardPaymentMethodSpecificInput $input */
        $input = $this->cardPaymentMethodSpecificInputFactory->create();
        $input->threeDSecure = $this->threeDSecureBuilder->create($order);
        $input->transactionChannel = self::TRANSACTION_CHANNEL;
        $input->paymentProductId = $payment->getAdditionalInformation('product');
        $input->requiresApproval = $requiresApproval;
        $input->tokenize = $payment->getAdditionalInformation('tokenize');

        $this->setUnscheduledCardOnFileInformation($input, $payment);

        $request->cardPaymentMethodSpecificInput = $input;

        return $request;
    }

    private function setUnscheduledCardOnFileInformation(CardPaymentMethodSpecificInput $input, Payment $payment): void
    {
        if ($input->tokenize) {
            $input->unscheduledCardOnFileSequenceIndicator = self::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_FIRST;
            $input->unscheduledCardOnFileRequestor = self::UNSCHEDULED_CARD_ON_FILE_REQUESTOR_CARDHOLDER_INITIATED;
            return;
        }

        $orderPaymentExtension = $payment->getExtensionAttributes();
        if ($orderPaymentExtension === null) {
            return;
        }

        $paymentToken = $orderPaymentExtension->getVaultPaymentToken();
        if ($paymentToken === null) {
            return;
        }

        $input->token = $paymentToken->getGatewayToken();
        $input->unscheduledCardOnFileSequenceIndicator = self::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_SUBSEQUENT;
        $input->unscheduledCardOnFileRequestor = self::UNSCHEDULED_CARD_ON_FILE_REQUESTOR_CARDHOLDER_INITIATED;
    }
}
