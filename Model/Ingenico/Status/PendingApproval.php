<?php

namespace Ingenico\Connect\Model\Ingenico\Status;

use Ingenico\Connect\Plugin\Magento\Sales\Model\Order\Payment\State\AbstractCommand;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Helper\Data;

class PendingApproval implements HandlerInterface
{
    /**
     * @param OrderInterface $order
     * @param AbstractOrderStatus $ingenicoStatus
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        /** @var OrderPaymentInterface|Order\Payment $payment */
        $payment = $order->getPayment();
        $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        $amount = Data::reformatMagentoAmount($amount);

        $payment->setIsTransactionClosed(false);
        $payment->setData(AbstractCommand::KEY_ORDER_MUST_BE_PENDING_PAYMENT, true);

        if ($order->getStatus() === 'pending') {
            // If the order is in "pending" status (for example, after a challenged authorize)
            // We need to call the "authorize()"-method directly, otherwise the order state doesn't get updated:
            $payment->authorize(false, $amount);
        } else {
            $payment->registerAuthorizationNotification($amount);
        }
    }
}
