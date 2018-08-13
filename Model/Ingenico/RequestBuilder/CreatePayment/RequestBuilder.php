<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\CreatePayment;

use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequestFactory;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\AbstractRequestBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common\FraudFieldsBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common\OrderBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput\RequestDecoratorFactory as MethodDecoratorFactory;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductSpecificInput\RequestDecoratorFactory as ProductDecoratorFactory;

/**
 * Class CreatePaymentRequestBuilder
 */
class RequestBuilder extends AbstractRequestBuilder
{

    /**
     * RequestBuilder constructor.
     * @param MethodDecoratorFactory $methodDecoratorFactory
     * @param ProductDecoratorFactory $productDecoratorFactory
     * @param OrderBuilder $orderBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     * @param CreatePaymentRequestFactory $createPaymentRequestFactory
     */
    public function __construct(
        MethodDecoratorFactory $methodDecoratorFactory,
        ProductDecoratorFactory $productDecoratorFactory,
        OrderBuilder $orderBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder,
        CreatePaymentRequestFactory $createPaymentRequestFactory
    ) {
        parent::__construct($methodDecoratorFactory, $productDecoratorFactory, $orderBuilder, $fraudFieldsBuilder);

        $this->requestObject = $createPaymentRequestFactory->create();
    }

    /**
     * @param Order $order
     * @return CreateHostedCheckoutRequest|CreatePaymentRequest
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(Order $order)
    {
        $request = parent::create($order);
        $payload = $order->getPayment()->getAdditionalInformation(Config::CLIENT_PAYLOAD_KEY);
        $request->encryptedCustomerInput = $payload;

        return $request;
    }
}
