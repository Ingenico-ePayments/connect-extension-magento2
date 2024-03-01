<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Model\Order;
use Worldline\Connect\Model\Worldline\Status\AbstractHandler as StatusAbstractHandler;

abstract class AbstractHandler extends StatusAbstractHandler
{
    public const KEY_ORDER = 'order';
    protected const EVENT_CATEGORY = 'payment';

    protected function dispatchEvent(Order $order, Payment $status)
    {
        $this->dispatchMagentoEvent([
            self::KEY_ORDER => $order,
            self::KEY_INGENICO_STATUS => $status,
        ]);
    }
}
