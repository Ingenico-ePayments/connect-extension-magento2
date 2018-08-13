<?php

namespace Netresearch\Epayments\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\Ingenico\Action\ApprovePayment;
use Netresearch\Epayments\Model\Ingenico\Action\CapturePayment;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Psr\Log\LoggerInterface;

class IngenicoCaptureCommand extends AbstractCommand implements CommandInterface
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
     * IngenicoCaptureCommand constructor.
     *
     * @param ManagerInterface $manager
     * @param LoggerInterface $logger
     * @param CapturePayment $capturePayment
     * @param ApprovePayment $approvePayment
     * @param StatusResponseManager $statusResponseManager
     */
    public function __construct(
        ManagerInterface $manager,
        LoggerInterface $logger,
        CapturePayment $capturePayment,
        ApprovePayment $approvePayment,
        StatusResponseManager $statusResponseManager
    ) {
        $this->capturePayment = $capturePayment;
        $this->approvePayment = $approvePayment;
        $this->statusResponseManager = $statusResponseManager;

        parent::__construct($manager, $logger);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $commandSubject)
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'];

        $paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        $status = $this->statusResponseManager->get($payment, $paymentId);

        try {
            if ($status->status == StatusInterface::PENDING_CAPTURE) {
                $this->capturePayment->process($payment->getOrder(), $amount);
            } elseif ($status->status == StatusInterface::PENDING_APPROVAL) {
                $this->approvePayment->process($payment->getOrder(), $amount);
            } elseif ($status->status == StatusInterface::CAPTURE_REQUESTED) {
                throw new LocalizedException(__('Payment is already captured'));
            } else {
                throw new LocalizedException(__('Unknown or invalid payment status'));
            }
        } catch (ResponseException $e) {
            $this->handleError($e);
        }
    }
}
