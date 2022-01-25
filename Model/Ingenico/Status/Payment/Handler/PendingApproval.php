<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Model\Ingenico\Token\TokenService;
use Ingenico\Connect\Plugin\Magento\Sales\Model\Order\Payment\State\AbstractCommand;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Helper\Data;

class PendingApproval extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'pending_approval';

    /**
     * @var TokenService
     */
    private $tokenService;

    public function __construct(ManagerInterface $eventManager, TokenService $tokenService)
    {
        parent::__construct($eventManager);

        $this->tokenService = $tokenService;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        /** @var OrderPaymentInterface|Order\Payment $payment */
        $payment = $order->getPayment();
        $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        $amount = Data::reformatMagentoAmount($amount);

        $payment->setIsTransactionClosed(false);
        $payment->setData(AbstractCommand::KEY_ORDER_MUST_BE_PENDING_PAYMENT, true);

        if ($order->getStatus() === 'pending') {
            // If the order is in "pending" status (for example, after a challenged authorize)
            // We need to call the "authorize()"-method directly, otherwise the order state doesn't get updated:
            $payment->authorize(false, $amount);
        }

        $this->dispatchEvent($order, $ingenicoStatus);

        if ($order instanceof Order) {
            $this->tokenService->createByOrderAndPayment($order, $ingenicoStatus);
        }
    }
}
