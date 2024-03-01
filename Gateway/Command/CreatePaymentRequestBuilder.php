<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command;

use Magento\Sales\Model\Order\Payment;

interface CreatePaymentRequestBuilder
{
    public function build(Payment $payment, bool $requiresApproval);
}
