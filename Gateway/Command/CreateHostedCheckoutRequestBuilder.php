<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Gateway\Command;

use Ingenico\Connect\Sdk\Domain\Definitions\PaymentProductFilter;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequestFactory;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\HostedCheckoutSpecificInput;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\HostedCheckoutSpecificInputFactory;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\PaymentProductFiltersHostedCheckout;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificInputFactory;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Gateway\Command\CreatePaymentRequest\RedirectRequestBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\FraudFieldsBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\MerchantBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\OrderBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\MethodSpecificInput\Card\ThreeDSecureBuilder;
use Worldline\Connect\Model\Worldline\Token\TokenServiceInterface;
use Worldline\Connect\PaymentMethod\PaymentMethods;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse

class CreateHostedCheckoutRequestBuilder implements CreatePaymentRequestBuilder
{
    /**
     * @var CreateHostedCheckoutRequestFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $createHostedCheckoutRequestFactory;

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
     * @var HostedCheckoutSpecificInputFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $hostedCheckoutSpecificInputFactory;

    /**
     * @var ResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $resolver;

    /**
     * @var UrlInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $urlBuilder;

    /**
     * @var TokenServiceInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $tokenService;

    /**
     * @var PaymentMethods
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentMethods;

    /**
     * @param CreateHostedCheckoutRequestFactory $createHostedCheckoutRequestFactory
     * @param OrderBuilder $orderBuilder
     * @param MerchantBuilder $merchantBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     * @param CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory
     * @param ThreeDSecureBuilder $threeDSecureBuilder
     * @param HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory
     * @param ResolverInterface $resolver
     * @param UrlInterface $urlBuilder
     * @param TokenServiceInterface $tokenService
     */
    public function __construct(
        CreateHostedCheckoutRequestFactory $createHostedCheckoutRequestFactory,
        OrderBuilder $orderBuilder,
        MerchantBuilder $merchantBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        ThreeDSecureBuilder $threeDSecureBuilder,
        HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory,
        ResolverInterface $resolver,
        UrlInterface $urlBuilder,
        TokenServiceInterface $tokenService,
        PaymentMethods $paymentMethods
    ) {
        $this->createHostedCheckoutRequestFactory = $createHostedCheckoutRequestFactory;
        $this->orderBuilder = $orderBuilder;
        $this->merchantBuilder = $merchantBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->threeDSecureBuilder = $threeDSecureBuilder;
        $this->hostedCheckoutSpecificInputFactory = $hostedCheckoutSpecificInputFactory;
        $this->resolver = $resolver;
        $this->urlBuilder = $urlBuilder;
        $this->tokenService = $tokenService;
        $this->paymentMethods = $paymentMethods;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function build(Payment $payment, string $paymentAction): CreateHostedCheckoutRequest
    {
        $order = $payment->getOrder();

        $input = $this->cardPaymentMethodSpecificInputFactory->create();
        $input->threeDSecure = $this->threeDSecureBuilder->create($order);
        // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
        $input->threeDSecure->redirectionData->returnUrl = $this->urlBuilder->getUrl(
            RedirectRequestBuilder::HOSTED_CHECKOUT_RETURN_URL
        );

        $input->transactionChannel = 'ECOMMERCE';
        $input->requiresApproval = $paymentAction === MethodInterface::ACTION_AUTHORIZE;
        $input->tokenize = $payment->getAdditionalInformation('tokenize');

        $orderPaymentExtension = $payment->getExtensionAttributes();
        if ($orderPaymentExtension !== null) {
            $paymentToken = $orderPaymentExtension->getVaultPaymentToken();
            if ($paymentToken !== null) {
                $input->token = $paymentToken->getGatewayToken();
            }
        }

        $request = $this->createHostedCheckoutRequestFactory->create();
        $request->order = $this->orderBuilder->create($order);
        $request->merchant = $this->merchantBuilder->create($order);
        $request->fraudFields = $this->fraudFieldsBuilder->create($order);
        $request->hostedCheckoutSpecificInput = $this->buildHostedCheckoutSpecificInput($payment);
        $request->cardPaymentMethodSpecificInput = $input;

        return $request;
    }

    /**
     * @param Order $order
     * @return HostedCheckoutSpecificInput
     */
    private function buildHostedCheckoutSpecificInput(Payment $payment)
    {
        $specificInput = $this->hostedCheckoutSpecificInputFactory->create();
        $specificInput->locale = $this->resolver->getLocale();
        $specificInput->returnUrl = $this->urlBuilder->getUrl(RedirectRequestBuilder::HOSTED_CHECKOUT_RETURN_URL);
        $specificInput->showResultPage = false;
        $specificInput->tokens = $this->getTokens($payment->getOrder());
        $specificInput->validateShoppingCart = true;
        $specificInput->returnCancelState = true;
        $specificInput->paymentProductFilters = $this->getPaymentProductFilters($payment);

        return $specificInput;
    }

    /**
     * @param Order $order
     * @return null|string  String of comma separated token values
     */
    private function getTokens(Order $order)
    {
        if ($order->getCustomerIsGuest() || !$order->getCustomerId()) {
            return null;
        }

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $tokens = implode(',', $this->tokenService->find($order->getCustomerId()));

        return $tokens === '' ? null : $tokens;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    private function getPaymentProductFilters(Payment $payment)
    {
        $paymentProductFilters = new PaymentProductFiltersHostedCheckout();
        $filter = new PaymentProductFilter();

        $productId = $payment->getMethodInstance()->getConfigData('product_id');
        if ($productId) {
            $filter->products = [$productId];
            $paymentProductFilters->restrictTo = $filter;
        }

        return $paymentProductFilters;
    }
}
