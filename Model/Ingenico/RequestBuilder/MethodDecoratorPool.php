<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder;

use Ingenico\Connect\Sdk\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * This Pool can apply payment method decorators configured via di.xml or return an individual decorator based on
 * an Ingenico payment method name.
 *
 * Class MethodDecoratorPool
 */
class MethodDecoratorPool implements DecoratorInterface
{
    /**
     * @var DecoratorInterface[]
     */
    private $decoratorPool;

    /**
     * MethodDecoratorPool constructor.
     *
     * @param DecoratorInterface[] $decoratorPool
     */
    public function __construct(array $decoratorPool)
    {
        $this->decoratorPool = $decoratorPool;
    }

    /**
     * @param string $paymentMethod
     * @return DecoratorInterface
     * @throws LocalizedException
     */
    public function get($paymentMethod)
    {
        if (isset($this->decoratorPool[$paymentMethod])) {
            return $this->decoratorPool[$paymentMethod];
        }
        throw new LocalizedException(
            __(
                "There is no MethodSpecificInput decorator for payment method '%1'.",
                $paymentMethod
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
