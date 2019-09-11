<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder;

use Ingenico\Connect\Sdk\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * This Pool can apply payment method decorators configured via di.xml or return an individual decorator based on
 * an Ingenico payment method name.
 *
 * Class ProductDecoratorPool
 */
class ProductDecoratorPool implements DecoratorInterface
{
    /**
     * @var DecoratorInterface[]
     */
    private $decoratorPool;

    /**
     * ProductDecoratorPool constructor.
     *
     * @param DecoratorInterface[] $decoratorPool
     */
    public function __construct(array $decoratorPool)
    {
        $this->decoratorPool = $decoratorPool;
    }

    /**
     * @param string $paymentMethodId
     * @return DecoratorInterface
     * @throws LocalizedException
     */
    public function get($paymentMethodId)
    {
        if (isset($this->decoratorPool[$paymentMethodId])) {
            return $this->decoratorPool[$paymentMethodId];
        }
        throw new LocalizedException(
            __(
                "There is no PaymentProductSpecificInput decorator for payment method id '%1'.",
                $paymentMethodId
            )
        );
    }

    /**
     * @param DataObject $request
     * @param OrderInterface $order
     * @return DataObject
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        foreach ($this->decoratorPool as $decorator) {
            try {
                $request = $decorator->decorate($request, $order);
            } catch (\Exception $e) {
                // to prevent execution failure of decorators down the line, we catch all exceptions and ignore them
            }
        }

        return $request;
    }
}
