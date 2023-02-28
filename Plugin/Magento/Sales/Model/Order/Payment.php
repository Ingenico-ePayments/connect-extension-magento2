<?php

declare(strict_types=1);

namespace Worldline\Connect\Plugin\Magento\Sales\Model\Order;

use Magento\Sales\Model\Order\Creditmemo;

class Payment
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function aroundAddTransaction(
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
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
