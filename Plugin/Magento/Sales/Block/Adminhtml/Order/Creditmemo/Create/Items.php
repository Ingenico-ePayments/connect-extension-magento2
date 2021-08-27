<?php

declare(strict_types=1);

namespace Ingenico\Connect\Plugin\Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create;

use Ingenico\Connect\Plugin\Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items as BaseItems;

class Items extends AbstractOrder
{
    public function aroundAddChild(
        BaseItems $subject,
        callable $proceed,
        ...$args
    ) {
        if (strpos($args[2]['onclick'], 'submitCreditMemoOffline') !== false &&
            !$this->allowOfflineRefund($subject->getOrder())
        ) {
            return null;
        }

        return $proceed(...$args);
    }
}
