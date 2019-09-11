<?php
namespace Ingenico\Connect\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface HandlerInterface
 * @package Ingenico\Connect\Model
 */
interface HandlerInterface
{
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $orderStatus);
}
