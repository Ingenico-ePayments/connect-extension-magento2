<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Helper\Data;
use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Model\Ingenico\Token\TokenService;
use Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture as IngenicoCapture;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;

class CaptureRequested extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'capture_requested';

    /**
     * @var Config
     */
    private $orderConfig;

    /**
     * @var TokenService
     */
    private $tokenService;

    public function __construct(
        ManagerInterface $eventManager,
        Config $orderConfig,
        TokenService $tokenService
    ) {
        parent::__construct($eventManager);
        $this->orderConfig = $orderConfig;
        $this->tokenService = $tokenService;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();

        if ($ingenicoStatus instanceof IngenicoPayment) {
            $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        } elseif ($ingenicoStatus instanceof IngenicoCapture) {
            $amount = $ingenicoStatus->captureOutput->amountOfMoney->amount;
        } else {
            throw new LocalizedException(__('Unknown order status.'));
        }

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionClosed(true);
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING));
        $payment->registerCaptureNotification(Data::reformatMagentoAmount($amount));

        $this->dispatchEvent($order, $ingenicoStatus);

        if ($order instanceof Order) {
            $this->tokenService->createByOrderAndPayment($order, $ingenicoStatus);
        }
    }
}
