<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Refund\Handler;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use LogicException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order\Creditmemo;

abstract class AbstractHandler extends \Ingenico\Connect\Model\Ingenico\Status\AbstractHandler
{
    public const KEY_CREDIT_MEMO = 'credit_memo';
    protected const EVENT_CATEGORY = 'refund';

    protected function dispatchEvent(CreditmemoInterface $creditMemo, RefundResult $ingenicoStatus)
    {
        $this->dispatchMagentoEvent([
            self::KEY_CREDIT_MEMO => $creditMemo,
            self::KEY_INGENICO_STATUS => $ingenicoStatus,
        ]);
    }

    protected function validateCreditMemo(CreditmemoInterface $creditMemo)
    {
        if (!$creditMemo instanceof Creditmemo) {
            throw new LogicException('Only ' . Creditmemo::class . ' is supported');
        }
    }
}
