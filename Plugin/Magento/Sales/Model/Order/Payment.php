<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Model\Order;

use Magento\Sales\Model\Order\Creditmemo;

class Payment
{
    public function aroundAddTransaction(
        \Magento\Sales\Model\Order\Payment $subject,
        callable $proceed,
        $type,
        $salesDocument = null,
        $failSafe = false
    ) {
        // Prevent unique constraint violations by returning the already existing refund transaction
        if ($salesDocument instanceof Creditmemo && $salesDocument->hasRefundTransaction()) {
            return $salesDocument->getRefundTransaction();
        }

        return $proceed($type, $salesDocument, $failSafe);
    }
}
