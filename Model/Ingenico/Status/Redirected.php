<?php

namespace Ingenico\Connect\Model\Ingenico\Status;

use Ingenico\Connect\Plugin\Magento\Sales\Model\Order\Payment\State\AbstractCommand;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

class Redirected implements HandlerInterface
{
    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $order->addCommentToStatusHistory(
            __('Redirected customer to finish payment process. Status: %status', ['status' => $ingenicoStatus->status])
        );

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
    }
}
