<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common;

use InvalidArgumentException;
use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodDecoratorPool;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\ProductDecoratorPool;
use LogicException;
use Magento\Sales\Model\Order\Payment;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use function array_key_exists;
use function sprintf;

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
     * @var MerchantBuilder
     */
    private $merchantBuilder;

    /**
     * @var FraudFieldsBuilder
     */
    private $fraudFieldsBuilder;

    public function __construct(
        MethodDecoratorPool $methodDecoratorPool,
        ProductDecoratorPool $productDecoratorPool,
        ConfigInterface $config,
        OrderBuilder $orderBuilder,
        MerchantBuilder $merchantBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder
    ) {
        $this->methodDecoratorPool = $methodDecoratorPool;
        $this->productDecoratorPool = $productDecoratorPool;
        $this->config = $config;
        $this->orderBuilder = $orderBuilder;
        $this->merchantBuilder = $merchantBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
    }

    /**
     * @param DataObject|CreateHostedCheckoutRequest|CreatePaymentRequest $ingenicoRequest
     * @param Order $order
     * @return DataObject|CreateHostedCheckoutRequest|CreatePaymentRequest
     */
    public function create($ingenicoRequest, Order $order)
    {
        $ingenicoRequest->order = $this->orderBuilder->create($order);
        $ingenicoRequest->merchant = $this->merchantBuilder->create($order);
        $ingenicoRequest->fraudFields = $this->fraudFieldsBuilder->create($order);

        $storeId = $order->getStoreId();
        /** @var Payment $payment */
        $payment = $order->getPayment();
        if ($this->config->getCheckoutType($storeId) === Config::CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT) {
            /**
             * Apply all decorators if checkout uses full Hosted Checkout redirect.
             */
            $ingenicoRequest = $this->methodDecoratorPool->decorate($ingenicoRequest, $order);
            $ingenicoRequest = $this->productDecoratorPool->decorate($ingenicoRequest, $order);
        } else {
            /**
             * Apply one specific decorator if only one is needed.
             */
            $this->validateOrderPaymentProductRestrictions($order);

            $paymentMethod = $payment->getAdditionalInformation(Config::PRODUCT_PAYMENT_METHOD_KEY);
            $paymentMethodId = $payment->getAdditionalInformation(Config::PRODUCT_ID_KEY);
            try {
                $methodDecorator = $this->methodDecoratorPool->get($paymentMethod);
                $ingenicoRequest = $methodDecorator->decorate($ingenicoRequest, $order);

                $productDecorator = $this->productDecoratorPool->get($paymentMethodId);
                $ingenicoRequest = $productDecorator->decorate($ingenicoRequest, $order);
                // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            } catch (\Exception $exception) {
                // might occur if no decorator is available
            }
        }

        return $ingenicoRequest;
    }

    /**
     * @throws LogicException
     */
    private function validateOrderPaymentProductRestrictions(Order $order)
    {
        $storeId = $order->getStoreId();
        $paymentProductId = $this->getPaymentProductIdFromOrder($order);
        $this->checkIfPaymentProductEnabled($paymentProductId, $storeId);
        $this->checkIfOrderWithinPaymentProductPriceRange($order, $paymentProductId, $storeId);
        $this->checkIfBillingCountryRestrictedForPaymentProduct($order, $paymentProductId, $storeId);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkIfPaymentProductEnabled(string $paymentProductId, int $storeId)
    {
        if (!$this->config->isPaymentProductEnabled($paymentProductId, $storeId)) {
            throw new InvalidArgumentException(sprintf(
                'Payment creation failed. Payment product with id "%s" is disabled.',
                $paymentProductId
            ));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkIfOrderWithinPaymentProductPriceRange(Order $order, string $paymentProductId, int $storeId)
    {
        $orderPrice = $order->getGrandTotal();
        $currencyCode = $order->getBaseCurrencyCode();
        if (!$this->config->isPriceInPaymentProductPriceRange(
            $orderPrice,
            $currencyCode,
            $paymentProductId,
            $storeId
        )) {
            throw new InvalidArgumentException(sprintf(
                'Payment creation failed. Grand total of "%s %s" is not within the price ranges of payment 
                product with id "%s".',
                $currencyCode,
                $orderPrice,
                $paymentProductId
            ));
        }
    }

    /**
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    private function checkIfBillingCountryRestrictedForPaymentProduct(
        Order $order,
        string $paymentProductId,
        int $orderId
    ) {
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress === null) {
            throw new LogicException('Order should have Billing Address.');
        }
        $countryId = $billingAddress->getCountryId();
        if ($this->config->isPaymentProductCountryRestricted($countryId, $paymentProductId, $orderId)) {
            throw new InvalidArgumentException(sprintf(
                'Payment creation failed. Payment product with id "%s" is disabled for country id "%s."',
                $paymentProductId,
                $countryId
            ));
        }
    }

    /**
     * @throws LogicException
     */
    private function getPaymentProductIdFromOrder(Order $order)
    {
        $payment = $order->getPayment();
        if ($payment === null) {
            throw new LogicException('Order should have Payment.');
        }
        $additionalInformation = $payment->getAdditionalInformation();

        if (array_key_exists(PaymentTokenInterface::PUBLIC_HASH, $additionalInformation) &&
            $additionalInformation[PaymentTokenInterface::PUBLIC_HASH] !== null
        ) {
            return 'cards';
        }

        $this->validateArrayHasKey($additionalInformation, Config::PRODUCT_PAYMENT_METHOD_KEY);
        $this->validateArrayHasKey($additionalInformation, Config::PRODUCT_ID_KEY);

        return $additionalInformation['ingenico_payment_product_method'] === 'card' ?
            'cards' : $additionalInformation['ingenico_payment_product_id'];
    }

    /**
     * @throws LogicException
     */
    private function validateArrayHasKey(array $array, string $key): void
    {
        if (!array_key_exists($key, $array)) {
            throw new LogicException(sprintf('Array should include "%s" key.', $key));
        }
    }
}
