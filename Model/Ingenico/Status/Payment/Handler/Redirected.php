<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Plugin\Magento\Sales\Model\Order\Payment\State\AbstractCommand;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;

class Redirected extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'redirected';

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, IngenicoPayment $ingenicoStatus)
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
        $order->getPayment()->setIsTransactionPending(true);

        /**
         * Make sure that the order will be set in a "pending" state:
         */
        $payment = $order->getPayment();
        if ($payment instanceof Payment) {
            $payment->setData(AbstractCommand::KEY_ORDER_MUST_BE_PENDING, true);
        }

        $this->dispatchEvent($order, $ingenicoStatus);
    }
}
