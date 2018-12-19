<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ShoppingCart;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ShoppingCartFactory;
use Magento\Sales\Model\Order;

/**
 * Class ShoppingCartBuilder
 */
class ShoppingCartBuilder
{
    /**
     * @var ShoppingCartFactory
     */
    private $shoppingCartFactory;

    /**
     * @var LineItemsBuilder
     */
    private $lineItemsBuilder;

    /**
     * ShoppingCartBuilder constructor.
     *
     * @param ShoppingCartFactory $shoppingCartFactory
     * @param LineItemsBuilder $lineItemsBuilder
     */
    public function __construct(
        ShoppingCartFactory $shoppingCartFactory,
        LineItemsBuilder $lineItemsBuilder
    ) {
        $this->shoppingCartFactory = $shoppingCartFactory;
        $this->lineItemsBuilder = $lineItemsBuilder;
    }

    /**
     * @param Order $order
     * @return ShoppingCart
     */
    public function create(Order $order)
    {
        $shoppingCart = $this->shoppingCartFactory->create();
        $shoppingCart->items = $this->lineItemsBuilder->create($order);

        return $shoppingCart;
    }
}
