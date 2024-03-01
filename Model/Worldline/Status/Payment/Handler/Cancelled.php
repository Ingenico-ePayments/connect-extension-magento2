<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\StatusResolver;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

use function abs;
use function sprintf;

class Cancelled extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'cancelled';

    public function __construct(
        ManagerInterface $eventManager,
        ConfigInterface $config,
        private readonly StatusResolver $statusResolver,
    ) {
        parent::__construct($eventManager, $config);
    }

    /**
     * {@inheritDoc}
     * @see Order::registerCancellation()
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function resolveStatus(Order $order, Payment $status)
    {
        if ($order->isCanceled()) {
            return;
        }

        foreach ($order->getAllItems() as $item) {
            $this->eventManager->dispatch('sales_order_item_cancel', ['item' => $item]);
            $item->setQtyCanceled($item->getQtyOrdered());
            $item->setTaxCanceled(
                $item->getTaxCanceled() + $item->getBaseTaxAmount() * $item->getQtyCanceled() / $item->getQtyOrdered()
            );
            $item->setDiscountTaxCompensationCanceled(
                $item->getDiscountTaxCompensationCanceled() +
                $item->getDiscountTaxCompensationAmount() * $item->getQtyCanceled() / $item->getQtyOrdered()
            );
        }

        $order->setSubtotalCanceled($order->getSubtotal());
        $order->setBaseSubtotalCanceled($order->getBaseSubtotal());

        $order->setTaxCanceled($order->getTaxAmount());
        $order->setBaseTaxCanceled($order->getBaseTaxAmount());

        $order->setShippingCanceled($order->getShippingAmount());
        $order->setBaseShippingCanceled($order->getBaseShippingAmount());

        $order->setDiscountCanceled(abs((float) $order->getDiscountAmount()));
        $order->setBaseDiscountCanceled(abs((float) $order->getBaseDiscountAmount()));

        $order->setTotalCanceled($order->getGrandTotal());
        $order->setBaseTotalCanceled($order->getBaseGrandTotal());

        $state = Order::STATE_CANCELED;
        $order->setState($state)->setStatus($this->statusResolver->getOrderStatusByState($order, $state));

        $order->addStatusHistoryComment(sprintf('Canceled Order with status %s', $status->status), false);

        $this->dispatchEvent($order, $status);
    }
}
