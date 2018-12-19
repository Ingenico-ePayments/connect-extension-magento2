<?php

namespace Netresearch\Epayments\Gateway\Command;

use Ingenico\Connect\Sdk\ResponseException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Model\Ingenico\Action\UndoCapturePaymentRequest;

/**
 * Class IngenicoVoidCommand
 *
 * @package Netresearch\Epayments\Gateway
 */
class IngenicoVoidCommand implements CommandInterface
{
    /**
     * @var UndoCapturePaymentRequest
     */
    private $undoCapturePaymentRequest;

    /**
     * @var ApiErrorHandler
     */
    private $apiErrorHandler;

    /**
     * IngenicoVoidCommand constructor.
     *
     * @param UndoCapturePaymentRequest $undoCapturePaymentRequest
     * @param ApiErrorHandler $apiErrorHandler
     */
    public function __construct(UndoCapturePaymentRequest $undoCapturePaymentRequest, ApiErrorHandler $apiErrorHandler)
    {
        $this->undoCapturePaymentRequest = $undoCapturePaymentRequest;
        $this->apiErrorHandler = $apiErrorHandler;
    }

    /**
     * @param mixed[] $commandSubject
     * @return void
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function execute(array $commandSubject)
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        try {
            $this->undoCapturePaymentRequest->process($payment->getOrder());
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }
}
