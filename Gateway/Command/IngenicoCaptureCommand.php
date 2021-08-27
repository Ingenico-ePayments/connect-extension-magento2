<?php

namespace Ingenico\Connect\Gateway\Command;

use Ingenico\Connect\Api\OrderPaymentManagementInterface;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\Action\ApprovePayment;
use Ingenico\Connect\Model\Ingenico\Action\CapturePayment;
use Ingenico\Connect\Model\Ingenico\Action\CreatePayment;
use Ingenico\Connect\Model\Ingenico\StatusInterface;

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
     * @var CreatePayment
     */
    private $createPayment;

    /**
     * @var ApiErrorHandler
     */
    private $apiErrorHandler;

    /**
     * @var OrderPaymentManagementInterface
     */
    private $orderPaymentManagement;

    /**
     * IngenicoCaptureCommand constructor.
     *
     * @param CapturePayment $capturePayment
     * @param ApprovePayment $approvePayment
     * @param CreatePayment $createPayment
     * @param ApiErrorHandler $apiErrorHandler
     * @param OrderPaymentManagementInterface $orderPaymentManagement
     */
    public function __construct(
        CapturePayment $capturePayment,
        ApprovePayment $approvePayment,
        CreatePayment $createPayment,
        ApiErrorHandler $apiErrorHandler,
        OrderPaymentManagementInterface $orderPaymentManagement
    ) {
        $this->capturePayment = $capturePayment;
        $this->approvePayment = $approvePayment;
        $this->createPayment = $createPayment;
        $this->apiErrorHandler = $apiErrorHandler;
        $this->orderPaymentManagement = $orderPaymentManagement;
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

        $status = $this->orderPaymentManagement->getIngenicoPaymentStatus($payment);

        try {
            switch ($status) {
                case StatusInterface::PENDING_CAPTURE:
                    $this->capturePayment->process($payment->getOrder(), $amount);
                    break;
                case StatusInterface::PENDING_APPROVAL:
                    $this->approvePayment->process($payment->getOrder(), $amount);
                    break;
                case StatusInterface::CAPTURE_REQUESTED:
                    throw new CommandException(__('Payment is already captured'));
                    break;
                default:
                    throw new CommandException(__('Unknown or invalid payment status'));
                    break;
            }
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
