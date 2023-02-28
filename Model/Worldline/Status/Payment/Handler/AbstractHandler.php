<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as WorldlinePayment;
use Magento\Sales\Api\Data\OrderInterface;

// phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
abstract class AbstractHandler extends \Worldline\Connect\Model\Worldline\Status\AbstractHandler
{
    public const KEY_ORDER = 'order';
    protected const EVENT_CATEGORY = 'payment';

    protected function addOrderComment(OrderInterface $order, WorldlinePayment $worldlineStatus)
    {
        $order->addCommentToStatusHistory(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            implode(
                '. ',
                [
                    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                    __($this->config->getPaymentStatusInfo($worldlineStatus->status)),
                    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                    __('Status: %1', $worldlineStatus->status),
                ]
            )
        );
    }

    protected function dispatchEvent(OrderInterface $order, Payment $worldlineStatus)
    {
        $this->dispatchMagentoEvent([
            self::KEY_ORDER => $order,
            self::KEY_INGENICO_STATUS => $worldlineStatus,
        ]);
    }
}
