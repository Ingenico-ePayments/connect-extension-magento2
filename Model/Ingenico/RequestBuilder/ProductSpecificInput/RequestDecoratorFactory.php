<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductSpecificInput;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * This factory uses path mappings from the configuration to determine which PaymentProductSpecificInput decorator
 * class belongs to which payment product id.
 * The neccessary information is stored in $order->getPayment()->getAdditionalinformation().
 *
 * Class RequestDecoratorFactory
 */
class RequestDecoratorFactory
{
    /**
     * @var array   Contains a 'productId => decoratorPath' map
     */
    private $decoratorMap;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * RequestDecoratorFactory constructor.
     *
     * @param array $decoratorMap Configured via di.xml
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
     * @param OrderInterface $order
     * @return DecoratorInterface
     * @throws LocalizedException
     */
    public function create(OrderInterface $order)
    {
        $decoratorPath = $this->getDecoratorPath($order);
        /** @var DecoratorInterface $requestDecorator */
        $requestDecorator = $this->objectManager->get($decoratorPath);

        return $requestDecorator;
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws LocalizedException
     */
    private function getDecoratorPath(OrderInterface $order)
    {
        /** @var string $paymentProductId */
        $paymentProductId = $order->getPayment()->getAdditionalInformation(
            \Netresearch\Epayments\Model\Config::PRODUCT_ID_KEY
        );
        if (isset($this->decoratorMap[$paymentProductId])) {
            return $this->decoratorMap[$paymentProductId];
        }
        throw new LocalizedException(
            __(
                "There is no PaymentProductSpecific decorator for payment product id '%1'.",
                $paymentProductId
            )
        );
    }
}
