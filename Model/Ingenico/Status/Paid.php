<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;

class Paid implements HandlerInterface
{
    /**
     * @var CapturedFactory
     */
    private $capturedFactory;

    /**
     * @var ConfigInterface
     */
    private $ePaymentsConfig;

    /**
     * @var StatusResponseManager
     */
    private $statusResponseManager;

    /**
     * Paid constructor.
     *
     * @param CapturedFactory $capturedFactory
     * @param ConfigInterface $ePaymentsConfig
     * @param StatusResponseManager $statusResponseManager
     */
    public function __construct(
        CapturedFactory $capturedFactory,
        ConfigInterface $ePaymentsConfig,
        StatusResponseManager $statusResponseManager
    ) {
        $this->capturedFactory = $capturedFactory;
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->statusResponseManager = $statusResponseManager;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $payment = $order->getPayment();
        $currentPaymentStatus = '';
        $captureTransaction = $this->statusResponseManager->getTransactionBy($ingenicoStatus->id);
        if ($captureTransaction !== null) {
            $currentCaptureStatus = $this->statusResponseManager->get($payment, $ingenicoStatus->id);
            $currentPaymentStatus = $currentCaptureStatus->status;
        }

        if ($currentPaymentStatus !== StatusInterface::CAPTURED) {
            /** @var Captured $capturedHandler */
            $capturedHandler = $this->capturedFactory->create();
            $capturedHandler->resolveStatus($order, $ingenicoStatus);
        }
        $order->addStatusHistoryComment($this->ePaymentsConfig->getPaymentStatusInfo(StatusInterface::PAID));
    }
}
