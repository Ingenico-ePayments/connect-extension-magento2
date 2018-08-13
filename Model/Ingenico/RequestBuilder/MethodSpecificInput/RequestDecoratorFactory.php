<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Magento\Config\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * This factory uses path mappings from the configuration to determine which PaymentMethodSpecificInput decorator
 * class belongs to which payment method group.
 * The neccessary information is stored in $order->getPayment()->getAdditionalinformation().
 *
 * Class RequestDecoratorFactory
 */
class RequestDecoratorFactory
{
    /**
     * @var array   Contains a 'paymentMethod => decoratorPath' map
     */
    private $decoratorMap;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * RequestDecoratorFactory constructor.
     *
     * @param array $decoratorMap   Configured via di.xml
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        array $decoratorMap,
        ObjectManagerInterface $objectManager
    ) {
        $this->decoratorMap = $decoratorMap;
        $this->objectManager = $objectManager;
    }

    /**
     * @param Order $order
     * @return DecoratorInterface
     * @throws LocalizedException
     */
    public function create(Order $order)
    {
        $decoratorPath = $this->getDecoratorPath($order);
        /** @var AbstractMethodDecorator $requestDecorator */
        $requestDecorator = $this->objectManager->get($decoratorPath);

        return $requestDecorator;
    }

    /**
     * @param Order $order
     * @return string
     * @throws LocalizedException
     */
    private function getDecoratorPath(Order $order)
    {
        /** @var string $currentPaymentMethod */
        $currentPaymentMethod = $order->getPayment()->getAdditionalInformation(
            \Netresearch\Epayments\Model\Config::PRODUCT_PAYMENT_METHOD_KEY
        );
        if (isset($this->decoratorMap[$currentPaymentMethod])) {
            return $this->decoratorMap[$currentPaymentMethod];
        }
        throw new LocalizedException(__(
            "There is no MethodSpecificInput decorator for payment method '%1'.",
            $currentPaymentMethod
        ));
    }
}
