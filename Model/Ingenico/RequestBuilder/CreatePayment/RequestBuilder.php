<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\CreatePayment;

use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequestFactory;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder as CommonRequestBuilder;

/**
 * Class CreatePaymentRequestBuilder
 */
class RequestBuilder
{
    /**
     * @var CommonRequestBuilder
     */
    private $requestBuilder;

    /**
     * @var CreatePaymentRequestFactory
     */
    private $createPaymentRequestFactory;

    /**
     * RequestBuilder constructor.
     *
     * @param CreatePaymentRequestFactory $createPaymentRequestFactory
     * @param CommonRequestBuilder $requestBuilder
     */
    public function __construct(
        CreatePaymentRequestFactory $createPaymentRequestFactory,
        CommonRequestBuilder $requestBuilder
    ) {
        $this->createPaymentRequestFactory = $createPaymentRequestFactory;
        $this->requestBuilder = $requestBuilder;
    }

    /**
     * @param Order $order
     * @return CreatePaymentRequest
     */
    public function create(Order $order)
    {
        $request = $this->createPaymentRequestFactory->create();
        $request = $this->requestBuilder->create($request, $order);

        $payload = $order->getPayment()->getAdditionalInformation(Config::CLIENT_PAYLOAD_KEY);
        $request->encryptedCustomerInput = $payload;

        return $request;
    }
}
