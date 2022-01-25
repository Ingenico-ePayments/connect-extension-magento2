<?php

namespace Ingenico\Connect\Model\Ingenico\Token;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Model\Order;

interface TokenServiceInterface
{
    /**
     * Get token values from db
     *
     * @param int $customerId
     * @return array
     */
    public function find($customerId);

    /**
     * @param int $customerId
     * @param string[] $tokens
     * @return void
     */
    public function deleteAll($customerId, $tokens = []);

    /**
     * @param Order $order
     * @param Payment $payment
     * @return void
     */
    public function createByOrderAndPayment(Order $order, Payment $payment);
}
