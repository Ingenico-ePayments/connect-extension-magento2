<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface HandlerInterface
 */
// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface HandlerInterface
{
    /**
     * @param OrderInterface $order
     * @param Payment $status
     * @return void
     * @throws LocalizedException
     */
    public function resolveStatus(OrderInterface $order, Payment $status);
}
