<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Interface HandlerInterface
 */
// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface HandlerInterface
{
    public function resolveStatus(Creditmemo $creditMemo, RefundResult $status);
}
