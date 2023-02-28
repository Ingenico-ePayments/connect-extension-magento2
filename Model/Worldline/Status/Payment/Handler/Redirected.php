<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as WorldlinePayment;
use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse

class Redirected extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'redirected';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, WorldlinePayment $worldlineStatus)
    {
        /**
         * For inline payments with redirect actions a transaction is created. If the transaction is not kept open,
         * a later online capture is impossible
         */
        $order->getPayment()->setIsTransactionClosed(false);

        /**
         * Mark the transaction as pending, otherwise the invoice will be marked as "paid"
         * and the order will be set to "processing"
         */


//        $order->getPayment()->setIsTransactionPending(true);

        $this->addOrderComment($order, $worldlineStatus);

        $this->dispatchEvent($order, $worldlineStatus);
    }
}
