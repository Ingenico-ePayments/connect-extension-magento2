<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Model\Order\Payment\State;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class AuthorizeCommand extends AbstractCommand
{
    public function afterExecute(
        \Magento\Sales\Model\Order\Payment\State\AuthorizeCommand $subject,
        $result,
        OrderPaymentInterface $payment,
        $amount,
        OrderInterface $order
    ) {
        $this->updateOrderStatus($payment, $order);

        return $result;
    }
}
