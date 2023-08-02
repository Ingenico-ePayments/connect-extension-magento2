<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface ResolverInterface
{
    /**
     * Pulls the responsible StatusInterface implementation for the status and lets them handle the order transition
     *
     * @param Order $order
     * @param Payment $payment
     * @throws LocalizedException
     */
    public function resolve(Order $order, Payment $payment);
}
