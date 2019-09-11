<?php

namespace Ingenico\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\Connect\Model\Ingenico\Action\RetrievePayment;
use Ingenico\Connect\Model\Ingenico\Action\UndoCapturePaymentRequest;

/**
 * Class UndoCapturePaymentObserver
 *
 * @package Ingenico\Connect\Observer
 */
class UndoCapturePaymentObserver implements ObserverInterface
{
    /**
     * @var UndoCapturePaymentRequest
     */
    private $undoCapturePaymentRequest;

    /**
     * @var RetrievePayment
     */
    private $retrievePayment;

    /**
     * UndoCapturePaymentObserver constructor.
     *
     * @param UndoCapturePaymentRequest $undoCapturePaymentRequest
     * @param RetrievePayment $retrievePayment
     */
    public function __construct(UndoCapturePaymentRequest $undoCapturePaymentRequest, RetrievePayment $retrievePayment)
    {
        $this->undoCapturePaymentRequest = $undoCapturePaymentRequest;
        $this->retrievePayment = $retrievePayment;
    }

    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getData('payment');

        // if ingenico payment
        if ($payment->getMethodInstance()->getCode() === ConfigProvider::CODE) {
            $this->undoCapturePaymentRequest->process($payment->getOrder());
            $this->retrievePayment->process($payment->getOrder());
        }
    }
}
