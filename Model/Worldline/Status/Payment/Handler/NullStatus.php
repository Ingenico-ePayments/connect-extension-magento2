<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

class NullStatus implements HandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Order $order, Payment $payment)
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        throw new LocalizedException(__('Status is not implemented'));
    }
}
