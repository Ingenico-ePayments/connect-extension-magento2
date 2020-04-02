<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Model\Order;

use Ingenico\Connect\Model\ConfigProvider;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class StateResolver
{
    public const KEY_ORDER_MUST_BE_HOLD = 'order_must_be_hold';

    /**
     * We need to explicitly override this method, because Magento's original
     * implementation will map a 'holded' state to a 'pending' status. This is
     * because the original class will see the initial state as 'new', which
     * in turn will make the default status 'pending'.
     *
     * @param Order\StateResolver $subject
     * @param $result
     * @param OrderInterface $order
     * @return string
     */
    public function afterGetStateForOrder(Order\StateResolver $subject, $result, OrderInterface $order)
    {
        if (!$order instanceof Order) {
            return $result;
        }

        if ($order->getPayment()->getMethod() !== ConfigProvider::CODE) {
            return $result;
        }

        if ($order->getData(self::KEY_ORDER_MUST_BE_HOLD)) {
            return Order::STATE_HOLDED;
        }

        return $result;
    }
}
