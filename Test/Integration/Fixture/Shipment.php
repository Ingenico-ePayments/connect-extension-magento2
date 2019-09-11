<?php

namespace Ingenico\Connect\Test\Integration\Fixture;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use \Magento\Sales\Model\Convert\Order as ConvertOrder;

class Shipment
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function createFromOrder(OrderInterface $order)
    {
        /** @var ConvertOrder $convertOrder */
        $convertOrder = $this->objectManager->get(ConvertOrder::class);
        $shipment = $convertOrder->toShipment($order);
        foreach ($order->getAllItems() as $orderItem) {
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qty = $orderItem->getQtyToShip();
            $shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
            $shipment->addItem($shipmentItem);
        }
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $shipment->save();
        $shipment->getOrder()->save();
        return $shipment;
    }
}
