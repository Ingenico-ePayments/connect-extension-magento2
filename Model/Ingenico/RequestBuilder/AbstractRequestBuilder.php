<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder;

use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common\FraudFieldsBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common\OrderBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput\RequestDecoratorFactory as MethodDecoratorFactory;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductSpecificInput\RequestDecoratorFactory as ProductDecoratorFactory;

/**
 * Abstract builder for Ingenico requests like CreateHostedCheckoutRequest or CreatePaymentRequest.
 * Use the decorator pattern to add specificInput objects to the request.
 *
 * Class AbstractRequestBuilder
 */
abstract class AbstractRequestBuilder
{
    const HOSTED_CHECKOUT_RETURN_URL = 'epayments/hostedCheckoutPage/processReturn';
    const REDIRECT_PAYMENT_RETURN_URL = 'epayments/inlinePayment/processReturn';

    /**
     * @var CreateHostedCheckoutRequest|CreatePaymentRequest
     */
    protected $requestObject;

    /**
     * @var MethodDecoratorFactory The request decorator is used to add the correct *MethodSpecificInput
     *                              property to the request object
     */
    private $methodDecoratorFactory;
    /**
     * @var ProductDecoratorFactory The request decorator is used to add the correct *ProductSpecificInput
     *                              property to the request object
     */
    private $productDecoratorFactory;

    /**
     * @var OrderBuilder
     */
    private $orderBuilder;

    /**
     * @var FraudFieldsBuilder
     */
    private $fraudFieldsBuilder;

    /**
     * AbstractRequestBuilder constructor.
     * @param MethodDecoratorFactory $methodDecoratorFactory
     * @param ProductDecoratorFactory $productDecoratorFactory
     * @param OrderBuilder $orderBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     */
    public function __construct(
        MethodDecoratorFactory $methodDecoratorFactory,
        ProductDecoratorFactory $productDecoratorFactory,
        OrderBuilder $orderBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder
    ) {
        $this->methodDecoratorFactory = $methodDecoratorFactory;
        $this->productDecoratorFactory = $productDecoratorFactory;
        $this->orderBuilder = $orderBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
    }

    /**
     * @param Order $order
     * @return CreateHostedCheckoutRequest|CreatePaymentRequest
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(Order $order)
    {
        $ingenicoRequest = $this->requestObject;

        $ingenicoRequest->fraudFields = $this->fraudFieldsBuilder->create();
        $ingenicoRequest->order = $this->orderBuilder->create($order);

        $methodDecorator = $this->methodDecoratorFactory->create($order);
        try {
            $ingenicoRequest = $methodDecorator->decorate($ingenicoRequest, $order);
        } catch (\Exception $exception) {
            // just don't blow up
        }

        try {
            $productDecorator = $this->productDecoratorFactory->create($order);
        } catch (LocalizedException $exception) {
            $productDecorator = false;
        }
        if ($productDecorator) {
            try {
                $ingenicoRequest = $productDecorator->decorate($ingenicoRequest, $order);
            } catch (\Exception $exception) {
                // just don't blow up
            }
        }

        return $ingenicoRequest;
    }
}
