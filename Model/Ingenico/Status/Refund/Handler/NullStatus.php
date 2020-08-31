<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Refund\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Refund\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;

class NullStatus implements HandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function resolveStatus(CreditmemoInterface $creditMemo, RefundResult $ingenicoStatus)
    {
        throw new LocalizedException(__('Status is not implemented.'));
    }
}
