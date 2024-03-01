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

class CaptureRequested extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'capture_requested';

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
        $orderPayment->setIsTransactionPending(false);
        $orderPayment->setIsTransactionClosed(true);

        $orderPayment->registerCaptureNotification($order->getBaseGrandTotal());

        $this->tokenService->createByOrderAndPayment($order, $status);

        $this->dispatchEvent($order, $status);
    }
}
