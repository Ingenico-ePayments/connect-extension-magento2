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
use Worldline\Connect\Model\StatusResponseManagerInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

/**
 * Class Captured
 *
 * @package Worldline\Connect\Model
 */
class Captured extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'captured';

    /**
     * @var StatusResponseManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusResponseManager;

    /**
     * @var Config
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderConfig;

    /**
     * Captured constructor.
     *
     * @param ManagerInterface $eventManager
     * @param Config $orderConfig
     * @param StatusResponseManagerInterface $statusResponseManager
     */
    public function __construct(
        ManagerInterface $eventManager,
        ConfigInterface $config,
        Config $orderConfig,
        StatusResponseManagerInterface $statusResponseManager
    ) {
        parent::__construct($eventManager, $config);
        $this->statusResponseManager = $statusResponseManager;
        $this->orderConfig = $orderConfig;
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

        $captureTransaction = $this->statusResponseManager->getTransactionBy($worldlineStatus->id, $payment);

        if ($order->getState() === Order::STATE_PAYMENT_REVIEW && $order->getStatus() === Order::STATUS_FRAUD) {
            $payment->setIsTransactionApproved(true);
            $payment->update(false);
        }

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionClosed(true);
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING));
        $payment->registerCaptureNotification(Data::reformatMagentoAmount($amount));

        if ($captureTransaction === null) {
            $payment->setNotificationResult(true);
        }

        $this->addOrderComment($order, $worldlineStatus);

        $this->dispatchEvent($order, $worldlineStatus);
    }
}
