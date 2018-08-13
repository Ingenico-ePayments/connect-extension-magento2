<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\CreateHostedCheckout;

use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequestFactory;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\HostedCheckoutSpecificInput;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\HostedCheckoutSpecificInputFactory;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\AbstractRequestBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common\FraudFieldsBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common\OrderBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput\RequestDecoratorFactory as MethodDecoratorFactory;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductSpecificInput\RequestDecoratorFactory as ProductDecoratorFactory;
use Netresearch\Epayments\Model\Ingenico\Token\TokenServiceInterface;

/**
 * Class CreateHostedCheckoutRequestBuilder
 */
class RequestBuilder extends AbstractRequestBuilder
{
    /**
     * @var TokenServiceInterface
     */
    private $tokenService;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var HostedCheckoutSpecificInputFactory
     */
    private $hostedCheckoutSpecificInputFactory;

    /**
     * RequestBuilder constructor.
     * @param MethodDecoratorFactory $methodDecoratorFactory
     * @param ProductDecoratorFactory $productDecoratorFactory
     * @param OrderBuilder $orderBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     * @param TokenServiceInterface $tokenService
     * @param ResolverInterface $resolver
     * @param UrlInterface $urlBuilder
     * @param CreateHostedCheckoutRequestFactory $createHostedCheckoutRequestFactory
     * @param HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory
     */
    public function __construct(
        MethodDecoratorFactory $methodDecoratorFactory,
        ProductDecoratorFactory $productDecoratorFactory,
        OrderBuilder $orderBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder,
        TokenServiceInterface $tokenService,
        ResolverInterface $resolver,
        UrlInterface $urlBuilder,
        CreateHostedCheckoutRequestFactory $createHostedCheckoutRequestFactory,
        HostedCheckoutSpecificInputFactory $hostedCheckoutSpecificInputFactory
    ) {
        parent::__construct($methodDecoratorFactory, $productDecoratorFactory, $orderBuilder, $fraudFieldsBuilder);

        $this->tokenService = $tokenService;
        $this->resolver = $resolver;
        $this->urlBuilder = $urlBuilder;
        $this->hostedCheckoutSpecificInputFactory = $hostedCheckoutSpecificInputFactory;

        $this->requestObject = $createHostedCheckoutRequestFactory->create();
    }

    /**
     * @param Order $order
     * @return CreateHostedCheckoutRequest|CreatePaymentRequest
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(Order $order)
    {
        $request = parent::create($order);

        $request->hostedCheckoutSpecificInput = $this->buildHostedCheckoutSpecificInput($order);

        return $request;
    }

    /**
     * @param Order $order
     * @return HostedCheckoutSpecificInput
     */
    private function buildHostedCheckoutSpecificInput(Order $order)
    {
        $specificInput = $this->hostedCheckoutSpecificInputFactory->create();
        $specificInput->locale = $this->resolver->getLocale();
        $specificInput->returnUrl = $this->urlBuilder->getUrl(self::HOSTED_CHECKOUT_RETURN_URL);
        $specificInput->showResultPage = false;
        $specificInput->tokens = $this->getTokens($order);
        $specificInput->validateShoppingCart = true;
        $specificInput->returnCancelState = true;

        return $specificInput;
    }

    /**
     * @param Order $order
     * @return null|string  String of comma separated token values
     */
    private function getTokens(Order $order)
    {
        $customerId = $order->getCustomerId();
        $tokenizationRequested = ($order->getPayment()->getAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY) === '1');
        if (!$customerId || !$tokenizationRequested) {
            return null;
        }
        $tokens = $this->tokenService->find($customerId);
        if (empty($tokens)) {
            return null;
        }
        return implode(',', $tokens);
    }
}
