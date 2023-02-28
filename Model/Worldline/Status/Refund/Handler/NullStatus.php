<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund\Handler;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;
use Worldline\Connect\Model\Worldline\Status\Refund\HandlerInterface;

class NullStatus implements HandlerInterface
{
    /**
     * {@inheritDoc}
     * @throws LocalizedException
     */
    public function resolveStatus(Creditmemo $creditMemo, RefundResult $worldlineStatus)
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        throw new LocalizedException(__('Status is not implemented.'));
    }
}
