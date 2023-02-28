<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Transaction;

use Magento\Sales\Api\Data\TransactionInterface;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface TransactionManagerInterface
{
    /**
     * Get a transaction by it's transaction ID
     *
     * @param string $txnId
     * @return TransactionInterface|null
     */
    public function retrieveTransaction(string $txnId): ?TransactionInterface;
}
