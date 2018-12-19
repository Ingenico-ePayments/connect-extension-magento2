<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodDecoratorPool;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductDecoratorPool;

/**
 * Builder for Ingenico requests like CreateHostedCheckoutRequest and CreatePaymentRequest.
 * Uses the decorator pool pattern to add specificInput objects to the request.
 *
 * Class RequestBuilder
 */
class RequestBuilder
{
    const HOSTED_CHECKOUT_RETURN_URL = 'epayments/hostedCheckoutPage/processReturn';
    const REDIRECT_PAYMENT_RETURN_URL = 'epayments/inlinePayment/processReturn';

    /**
     * @var MethodDecoratorPool
     */
    private $methodDecoratorPool;

    /**
     * @var ProductDecoratorPool
     */
    private $productDecoratorPool;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var OrderBuilder
     */
    private $orderBuilder;

    /**
     * @var FraudFieldsBuilder
     */
    private $fraudFieldsBuilder;

    /**
     * RequestBuilder constructor.
     *
     * @param MethodDecoratorPool $methodDecoratorPool
     * @param ProductDecoratorPool $productDecoratorPool
     * @param ConfigInterface $config
     * @param OrderBuilder $orderBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     */
    public function __construct(
        MethodDecoratorPool $methodDecoratorPool,
        ProductDecoratorPool $productDecoratorPool,
        ConfigInterface $config,
        OrderBuilder $orderBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder
    ) {
        $this->methodDecoratorPool = $methodDecoratorPool;
        $this->productDecoratorPool = $productDecoratorPool;
        $this->config = $config;
        $this->orderBuilder = $orderBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
    }

    /**
     * @param DataObject|CreateHostedCheckoutRequest|CreatePaymentRequest $ingenicoRequest
     * @param Order $order
     * @return DataObject|CreateHostedCheckoutRequest|CreatePaymentRequest
     */
    public function create($ingenicoRequest, Order $order)
    {
        $ingenicoRequest->fraudFields = $this->fraudFieldsBuilder->create();
        $ingenicoRequest->order = $this->orderBuilder->create($order);

        if ($this->config->getCheckoutType($order->getStoreId()) === Config::CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT) {
            /**
             * Apply all decorators if checkout uses full Hosted Checkout redirect.
             */
            $ingenicoRequest = $this->methodDecoratorPool->decorate($ingenicoRequest, $order);
            $ingenicoRequest = $this->productDecoratorPool->decorate($ingenicoRequest, $order);
        } else {
            /**
             * Apply one specific decorator if only one is needed.
             */
            $paymentMethod = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_PAYMENT_METHOD_KEY);
            $paymentMethodId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);
            try {
                $methodDecorator = $this->methodDecoratorPool->get($paymentMethod);
                $ingenicoRequest = $methodDecorator->decorate($ingenicoRequest, $order);

                $productDecorator = $this->productDecoratorPool->get($paymentMethodId);
                $ingenicoRequest = $productDecorator->decorate($ingenicoRequest, $order);
            } catch (\Exception $exception) {
                // might occur if no decorator is available
            }
        }

        return $ingenicoRequest;
    }
}
