<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Api\Data\OrderInterface;

abstract class AbstractHandler extends \Ingenico\Connect\Model\Ingenico\Status\AbstractHandler
{
    public const KEY_ORDER = 'order';
    protected const EVENT_CATEGORY = 'payment';

    protected function dispatchEvent(OrderInterface $order, Payment $ingenicoStatus)
    {
        $this->dispatchMagentoEvent([
            self::KEY_ORDER => $order,
            self::KEY_INGENICO_STATUS => $ingenicoStatus,
        ]);
    }
}
