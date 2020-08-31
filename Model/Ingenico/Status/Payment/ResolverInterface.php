<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

interface ResolverInterface
{
    /**
     * Pulls the responsible StatusInterface implementation for the status and lets them handle the order transition
     *
     * @param OrderInterface $order
     * @param Payment $status
     * @throws LocalizedException
     */
    public function resolve(OrderInterface $order, Payment $status);
}
