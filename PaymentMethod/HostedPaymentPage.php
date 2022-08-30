<?php

declare(strict_types=1);

namespace Ingenico\Connect\PaymentMethod;

use Ingenico\Connect\Gateway\Command\ApiErrorHandler;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\FraudFieldsBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\MerchantBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\OrderBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder as CommonRequestBuilder;
use Ingenico\Connect\Model\Ingenico\Token\TokenServiceInterface;
use Ingenico\Connect\Sdk\Domain\Definitions\PaymentProductFilter;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequestFactory;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\HostedCheckoutSpecificInput;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\HostedCheckoutSpecificInputFactory;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\PaymentProductFiltersHostedCheckout;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use function implode;
use function preg_replace;
use function uniqid;

class HostedPaymentPage implements CommandInterface
{
    /**
     * @var ApiErrorHandler
     */
    private $apiErrorHandler;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var CreateHostedCheckoutRequestFactory
     */
    private $createHostedCheckoutRequestFactory;

    /**
     * @var HostedCheckoutSpecificInputFactory
     */
    private $hostedCheckoutSpecificInputFactory;

    /**
     * @var OrderBuilder
     */
    private $orderBuilder;

    /**
     * @var MerchantBuilder
     */
    private $merchantBuilder;

    /**
     * @var FraudFieldsBuilder
     */
    private $fraudFieldsBuilder;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var TokenServiceInterface
     */
    private $tokenService;

    /**
     * @param ApiErrorHandler $apiErrorHandler
     * @param ResolverInterface $resolver
     * @param ConfigInterface $config
     * @param CreateHostedCheckoutRequestFactory $createHostedCheckoutRequestFactory
     * @param HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory
     * @param OrderBuilder $orderBuilder
     * @param MerchantBuilder $merchantBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     * @param UrlInterface $urlBuilder
     * @param ClientInterface $client
     * @param TokenServiceInterface $tokenService
     */
    public function __construct(
        ApiErrorHandler $apiErrorHandler,
        ResolverInterface $resolver,
        ConfigInterface $config,
        CreateHostedCheckoutRequestFactory $createHostedCheckoutRequestFactory,
        HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory,
        OrderBuilder $orderBuilder,
        MerchantBuilder $merchantBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder,
        UrlInterface $urlBuilder,
        ClientInterface $client,
        TokenServiceInterface $tokenService
    ) {
        $this->apiErrorHandler = $apiErrorHandler;
        $this->resolver = $resolver;
        $this->config = $config;
        $this->createHostedCheckoutRequestFactory = $createHostedCheckoutRequestFactory;
        $this->hostedCheckoutSpecificInputFactory = $hostedCheckoutSpecificInputFactory;
        $this->orderBuilder = $orderBuilder;
        $this->merchantBuilder = $merchantBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
        $this->urlBuilder = $urlBuilder;
        $this->client = $client;
        $this->tokenService = $tokenService;
    }

    public function execute(array $commandSubject)
    {
        /** @var Order\Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $order = $payment->getOrder();

        try {
            $scopeId = $order->getStoreId();
            $checkoutSubdomain = $this->config->getHostedCheckoutSubDomain($scopeId);

            /** @var InfoInterface $payment */
            $payment = $order->getPayment();

            $request = $this->createHostedCheckoutRequestFactory->create();
            $request->order = $this->orderBuilder->create($order);
            $request->merchant = $this->merchantBuilder->create($order);
            $request->fraudFields = $this->fraudFieldsBuilder->create($order);
            $request->hostedCheckoutSpecificInput = $this->buildHostedCheckoutSpecificInput($order);

            $payment->setAdditionalInformation(
                Config::IDEMPOTENCE_KEY,
                uniqid(
                    preg_replace(
                        '#\s+#',
                        '.',
                        $order->getStoreName()
                    ) . '.',
                    true
                )
            );

            $response = $this->client->createHostedCheckout($request, $scopeId);
            $ingenicoRedirectUrl = $checkoutSubdomain . $response->partialRedirectUrl;

            $payment->setAdditionalInformation(Config::REDIRECT_URL_KEY, $ingenicoRedirectUrl);
            $payment->setAdditionalInformation(Config::HOSTED_CHECKOUT_ID_KEY, $response->hostedCheckoutId);
            $payment->setAdditionalInformation(Config::RETURNMAC_KEY, $response->RETURNMAC);

            $stateObject = $commandSubject['stateObject'];
            $stateObject->setState(Order::STATE_NEW);
            $stateObject->setStatus('pending');
            $stateObject->setIsNotified(false);
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }

    /**
     * @param Order $order
     * @return HostedCheckoutSpecificInput
     */
    private function buildHostedCheckoutSpecificInput(Order $order)
    {
        $specificInput = $this->hostedCheckoutSpecificInputFactory->create();
        $specificInput->locale = $this->resolver->getLocale();
        $specificInput->returnUrl = $this->urlBuilder->getUrl(CommonRequestBuilder::HOSTED_CHECKOUT_RETURN_URL);
        $specificInput->showResultPage = false;
        $specificInput->tokens = $this->getTokens($order);
        $specificInput->validateShoppingCart = true;
        $specificInput->returnCancelState = true;
        if ($variant = $this->getHostedCheckoutVariant($order)) {
            $specificInput->variant = $variant;
        }
        if ($paymentProductFilters = $this->getPaymentProductFilters($order)) {
            $specificInput->paymentProductFilters = $paymentProductFilters;
        }

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

        $tokens = implode(',', $this->tokenService->find($order->getCustomerId()));

        return $tokens === '' ? null : $tokens;
    }

    private function getPaymentProductFilters(Order $order)
    {
        $payment = $order->getPayment();
        if ($payment === null) {
            return null;
        }
        if ($payment->getAdditionalInformation(Config::PRODUCT_ID_KEY) === 'cards' ||
            $payment->getMethod() === PaymentMethods::CARDS
        ) {
            $paymentProductFilters = new PaymentProductFiltersHostedCheckout();
            $restrictTo = new PaymentProductFilter();
            $restrictTo->groups = ['cards'];
            $paymentProductFilters->restrictTo = $restrictTo;
            return $paymentProductFilters;
        }
        $productId = $payment->getAdditionalInformation('product');
        if ($productId !== null) {
            $paymentProductFilters = new PaymentProductFiltersHostedCheckout();
            $restrictTo = new PaymentProductFilter();
            $restrictTo->products = [$productId];
            $paymentProductFilters->restrictTo = $restrictTo;
            return $paymentProductFilters;
        }
        return null;
    }

    private function getHostedCheckoutVariant(Order $order)
    {
        if ($order->getCustomerIsGuest()) {
            return $this->config->getHostedCheckoutGuestVariant(($order->getStoreId()));
        }
        return $this->config->getHostedCheckoutVariant($order->getStoreId());
    }
}
