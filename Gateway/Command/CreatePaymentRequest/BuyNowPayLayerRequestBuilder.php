<?php

declare(strict_types=1);

namespace Worldline\Connect\Gateway\Command\CreatePaymentRequest;

use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Gateway\Command\CreatePaymentRequestBuilder;

class BuyNowPayLayerRequestBuilder implements CreatePaymentRequestBuilder
{
    public function build(Payment $payment, bool $requiresApproval)
    {
    }
}
