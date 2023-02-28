<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Refund\Handler;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use LogicException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order\Creditmemo;

// phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
abstract class AbstractHandler extends \Worldline\Connect\Model\Worldline\Status\AbstractHandler
{
    public const KEY_CREDIT_MEMO = 'credit_memo';
    protected const EVENT_CATEGORY = 'refund';

    protected function dispatchEvent(CreditmemoInterface $creditMemo, RefundResult $worldlineStatus)
    {
        $this->dispatchMagentoEvent([
            self::KEY_CREDIT_MEMO => $creditMemo,
            self::KEY_INGENICO_STATUS => $worldlineStatus,
        ]);
    }

    protected function addCreditmemoComment(CreditmemoInterface $creditMemo, RefundResult $worldlineStatus)
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $creditMemo->addComment(implode(
            '. ',
            [
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __($this->config->getRefundStatusInfo($worldlineStatus->status)),
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __('Status: %1', $worldlineStatus->status),
            ]
        ));
    }

    protected function validateCreditMemo(CreditmemoInterface $creditMemo)
    {
        if (!$creditMemo instanceof Creditmemo) {
            throw new LogicException('Only ' . Creditmemo::class . ' is supported');
        }
    }
}
