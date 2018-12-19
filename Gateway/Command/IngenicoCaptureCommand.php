<?php

namespace Netresearch\Epayments\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\Ingenico\Action\ApprovePayment;
use Netresearch\Epayments\Model\Ingenico\Action\CapturePayment;
use Netresearch\Epayments\Model\Ingenico\Action\CreatePayment;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;

class IngenicoCaptureCommand implements CommandInterface
{
    /**
     * @var CapturePayment
     */
    private $capturePayment;

    /**
     * @var ApprovePayment
     */
    private $approvePayment;

    /**
     * @var StatusResponseManager
     */
    private $statusResponseManager;

    /**
     * @var CreatePayment
     */
    private $createPayment;

    /**
     * @var ApiErrorHandler
     */
    private $apiErrorHandler;

    /**
     * IngenicoCaptureCommand constructor.
     *
     * @param CapturePayment $capturePayment
     * @param ApprovePayment $approvePayment
     * @param StatusResponseManager $statusResponseManager
     * @param CreatePayment $createPayment
     * @param ApiErrorHandler $apiErrorHandler
     */
    public function __construct(
        CapturePayment $capturePayment,
        ApprovePayment $approvePayment,
        StatusResponseManager $statusResponseManager,
        CreatePayment $createPayment,
        ApiErrorHandler $apiErrorHandler
    ) {
        $this->capturePayment = $capturePayment;
        $this->approvePayment = $approvePayment;
        $this->statusResponseManager = $statusResponseManager;
        $this->createPayment = $createPayment;
        $this->apiErrorHandler = $apiErrorHandler;
    }

    /**
     * @param mixed[] $commandSubject
     * @return void
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'];
        $order = $payment->getOrder();

        if ($order->getEntityId() === null) {
            // new order, capture process
            $this->createPayment->create($order);
            $payment->setAdditionalInformation(Config::CLIENT_PAYLOAD_KEY, null);

            return;
        }

        // Admin capture process
        $paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        $status = $this->statusResponseManager->get($payment, $paymentId);

        try {
            if ($status->status == StatusInterface::PENDING_CAPTURE) {
                $this->capturePayment->process($payment->getOrder(), $amount);
            } elseif ($status->status == StatusInterface::PENDING_APPROVAL) {
                $this->approvePayment->process($payment->getOrder(), $amount);
            } elseif ($status->status == StatusInterface::CAPTURE_REQUESTED) {
                throw new CommandException(__('Payment is already captured'));
            } else {
                throw new CommandException(__('Unknown or invalid payment status'));
            }
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
