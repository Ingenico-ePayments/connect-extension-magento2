<?php
namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface HandlerInterface
 * @package Netresearch\Epayments\Model
 */
interface HandlerInterface
{
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $orderStatus);
}
