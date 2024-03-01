<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;
use Worldline\Connect\Model\Worldline\Token\TokenService;

class PendingApproval extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_approval';

    /**
     * @var TokenService
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $tokenService;

    public function __construct(
        ManagerInterface $eventManager,
        ConfigInterface $config,
        TokenService $tokenService
    ) {
        parent::__construct($eventManager, $config);
        $this->tokenService = $tokenService;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Order $order, Payment $status)
    {
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus(Order::STATE_PROCESSING);

        /** @var OrderPayment $orderPayment */
        $orderPayment = $order->getPayment();
        $orderPayment->setIsTransactionClosed(false);
        $orderPayment->setIsTransactionPending(true);

        $orderPayment->registerAuthorizationNotification($order->getBaseGrandTotal());

        $this->dispatchEvent($order, $status);
    }
}
