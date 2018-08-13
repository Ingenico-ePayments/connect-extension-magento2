<?php

namespace Netresearch\Epayments\Model\Ingenico\GlobalCollect;

use Ingenico\Connect\Sdk\Domain\Definitions\AmountOfMoney;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderReferences;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PaymentOutput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PaymentStatusOutput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RefundOutput;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Netresearch\Epayments\Model\Ingenico\GlobalCollect\Wx\DataRecord;
use Netresearch\Epayments\Model\Ingenico\GlobalCollect\Wx\PaymentData;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;

class OrderStatusFactory
{
    /**
     * @var array
     */
    private $refundStatuses = [
        StatusInterface::REFUND_REQUESTED,
        StatusInterface::REFUNDED,
    ];

    /**
     * @param $status
     * @param DataRecord $dataRecord
     * @return Payment|RefundResult
     */
    public function create($status, DataRecord $dataRecord)
    {
        if (\in_array($status, $this->refundStatuses, true)) {
            return $this->createRefundObject($status, $dataRecord);
        }
        return $this->createPaymentObject($status, $dataRecord);
    }

    /**
     * @param $status
     * @param DataRecord $transactionObject
     * @return RefundResult
     */
    private function createRefundObject($status, DataRecord $transactionObject)
    {
        $refundObject = new RefundResult();
        $refundObject->status = $status;
        $refundObject->id = $transactionObject->getConnectPaymentReference();
        $refundObject->refundOutput = $this->prepareRefundOutput($transactionObject->getPaymentData());
        $refundObject->statusOutput = $this->prepareStatusOutput($transactionObject->getPaymentData());
        return $refundObject;
    }

    /**
     * @param $status
     * @param DataRecord $transactionObject
     * @return Payment
     */
    private function createPaymentObject($status, DataRecord $transactionObject)
    {
        $paymentObject = new Payment();
        $paymentObject->status = $status;
        $paymentObject->id = $transactionObject->getConnectPaymentReference();
        $paymentObject->paymentOutput = $this->preparePaymentOutput($transactionObject->getPaymentData());
        $paymentObject->statusOutput = $this->prepareStatusOutput($transactionObject->getPaymentData());
        return $paymentObject;
    }

    /**
     * @param PaymentData $transactionObject
     * @return RefundOutput
     */
    private function prepareRefundOutput(PaymentData $transactionObject)
    {
        $refundOutput = new RefundOutput();
        $refundOutput->amountPaid = $transactionObject->getAmountDelivered();
        $refundOutput->references = $this->prepareReferences($transactionObject);
        return $refundOutput;
    }

    /**
     * @param PaymentData $transactionObject
     * @return PaymentOutput
     */
    private function preparePaymentOutput(PaymentData $transactionObject)
    {
        $paymentOutput = new PaymentOutput();
        $paymentOutput->amountPaid = $transactionObject->getAmountDelivered();
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->amount = $transactionObject->getAmountDelivered();
        $amountOfMoney->currencyCode = $transactionObject->getCurrencyDelivered();
        $paymentOutput->amountOfMoney = $amountOfMoney;
        $paymentOutput->references = $this->prepareReferences($transactionObject);
        return $paymentOutput;
    }

    /**
     * @param PaymentData $transactionObject
     * @return PaymentStatusOutput
     */
    private function prepareStatusOutput(PaymentData $transactionObject)
    {
        $statusOutput = new PaymentStatusOutput();
        $statusOutput->statusCode = $transactionObject->getPaymentStatus();
        $statusOutput->statusCodeChangeDateTime = $transactionObject->getTransactionDateTime();
        return $statusOutput;
    }

    /**
     * @param PaymentData $transactionObject
     * @return OrderReferences
     */
    private function prepareReferences(PaymentData $transactionObject)
    {
        $references = new OrderReferences();
        $references->merchantOrderId = $transactionObject->getMerchantOrderID();
        $references->merchantReference = $transactionObject->getAdditionalReference();
        return $references;
    }
}
