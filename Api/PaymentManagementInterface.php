<?php

declare(strict_types=1);

namespace Ingenico\Connect\Api;

use Magento\Sales\Api\Data\InvoiceInterface;

interface PaymentManagementInterface
{
    /**
     * Cancel a transaction with the status CAPTURE_REQUESTED on the remote API.
     *
     * @param InvoiceInterface $invoice
     * @api
     */
    public function cancelApproval(InvoiceInterface $invoice): void;
}
