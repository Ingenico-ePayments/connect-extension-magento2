<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\PaymentException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * Class Resolver
 * @package Netresearch\Epayments\Model
 */
interface ResolverInterface
{
    const TYPE_CAPTURE = 'capture';
    const TYPE_PAYMENT = 'payment';
    const TYPE_REFUND = 'refund';

    /**
     * Pulls the responsible StatusInterface implementation for the status and lets them handle the order transition
     *
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws NotFoundException
     * @throws PaymentException
     * @throws LocalizedException
     */
    public function resolve(OrderInterface $order, AbstractOrderStatus $ingenicoStatus);

    /**
     * @param string $type
     * @param string $status
     * @return HandlerInterface
     */
    public function getHandlerByType($type, $status);
}
