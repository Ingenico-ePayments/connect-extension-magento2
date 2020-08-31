<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class Cancelled extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'cancelled';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        if ($order instanceof Order) {
            $order->registerCancellation(
                "Canceled Order with status {$ingenicoStatus->status}"
            );
        }

        $this->dispatchEvent($order, $ingenicoStatus);
    }
}
