<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 * Interface HandlerInterface
 */
interface HandlerInterface
{
    /**
     * @param CreditmemoInterface $creditMemo
     * @param RefundResult $status
     * @return void
     * @throws LocalizedException
     */
    public function resolveStatus(CreditmemoInterface $creditMemo, RefundResult $status);
}
