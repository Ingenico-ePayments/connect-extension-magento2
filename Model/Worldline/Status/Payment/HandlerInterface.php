<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

/**
 * Interface HandlerInterface
 */
// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface HandlerInterface
{
    /**
     * @throws LocalizedException
     */
    public function resolveStatus(Order $order, Payment $status);
}
