<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface ResolverInterface
{
    /**
     * Pulls the responsible StatusInterface implementation for the status and lets them handle the order transition
     *
     * @param CreditmemoInterface $creditMemo
     * @param RefundResult $status
     * @throws LocalizedException
     */
    public function resolve(CreditmemoInterface $creditMemo, RefundResult $status);
}
