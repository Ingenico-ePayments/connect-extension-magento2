<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

class Cancelled extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'cancelled';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $worldlineStatus)
    {
        if ($order instanceof Order && !$order->isCanceled()) {
            $order->registerCancellation(
                // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
                "Canceled Order with status {$worldlineStatus->status}",
                false
            );
        }

        $this->dispatchEvent($order, $worldlineStatus);
    }
}
