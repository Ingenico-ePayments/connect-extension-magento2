<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture as WorldlineCapture;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as WorldlinePayment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use Worldline\Connect\Helper\Data;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;
use Worldline\Connect\Model\Worldline\Token\TokenService;

class CaptureRequested extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'capture_requested';

    /**
     * @var Config
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderConfig;

    /**
     * @var TokenService
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $tokenService;

    public function __construct(
        ManagerInterface $eventManager,
        ConfigInterface $config,
        Config $orderConfig,
        TokenService $tokenService
    ) {
        parent::__construct($eventManager, $config);
        $this->orderConfig = $orderConfig;
        $this->tokenService = $tokenService;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $worldlineStatus)
    {
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();

        if ($worldlineStatus instanceof WorldlinePayment) {
            $amount = $worldlineStatus->paymentOutput->amountOfMoney->amount;
        } elseif ($worldlineStatus instanceof WorldlineCapture) {
            $amount = $worldlineStatus->captureOutput->amountOfMoney->amount;
        } else {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Unknown order status.'));
        }

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionClosed(true);

//        $order->setState(Order::STATE_PROCESSING);
//        $order->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING));

        $payment->registerCaptureNotification(Data::reformatMagentoAmount($amount));

        $this->addOrderComment($order, $worldlineStatus);

        $this->dispatchEvent($order, $worldlineStatus);

        if ($order instanceof Order) {
            $this->tokenService->createByOrderAndPayment($order, $worldlineStatus);
        }
    }
}
