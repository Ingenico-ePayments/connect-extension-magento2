<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Transaction;

use Magento\Sales\Api\Data\TransactionInterface;

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
