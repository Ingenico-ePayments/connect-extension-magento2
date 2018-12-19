<?php

namespace Netresearch\Epayments\Model;

use Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Errors\Definitions\APIError;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

/**
 * Class StatusResponseManager
 *
 * @package Netresearch\Epayments\Model
 */
class StatusResponseManager implements StatusResponseManagerInterface
{
    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * StatusResponseManager constructor.
     *
     * @param TransactionManager $transactionManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        TransactionManager $transactionManager,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->transactionManager = $transactionManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Retrieve last PaymentResponse object stored in transaction additionalInformation. It contains canonical
     * information about a payment, such as isCancellable or isRefundable and isAuthorized values.
     *
     * @param InfoInterface|Payment $payment
     * @param string $transactionId
     * @return IngenicoPayment|false
     */
    public function get(InfoInterface $payment, $transactionId)
    {
        $orderStatus = false;
        /** @var Payment\Transaction $transaction */
        $transaction = $this->transactionManager->retrieveTransaction($transactionId);

        if ($transaction !== null && $classPath = $transaction->getAdditionalInformation(self::TRANSACTION_CLASS_KEY)) {
            /** @var IngenicoPayment $orderStatus */
            $orderStatus = new $classPath();
            $orderStatus = $orderStatus->fromJson(
                $transaction->getAdditionalInformation(self::TRANSACTION_INFO_KEY)
            );
        } elseif ($additionalInfo = $payment->getTransactionAdditionalInfo()) {
            // If transaction does not yet exist
            $classPath = $additionalInfo[self::TRANSACTION_CLASS_KEY];
            /** @var IngenicoPayment $orderStatus */
            $orderStatus = new $classPath();
            $orderStatus = $orderStatus->fromJson(
                $additionalInfo[self::TRANSACTION_INFO_KEY]
            );
        }

        return $orderStatus;
    }

    /**
     * Update the PaymentResponse object stored in a transaction.
     *
     * @param InfoInterface|Payment $payment
     * @param $transactionId
     * @param AbstractOrderStatus $orderStatus
     * @throws LocalizedException
     */
    public function set(
        InfoInterface $payment,
        $transactionId,
        AbstractOrderStatus $orderStatus
    ) {
        if (!property_exists($orderStatus, 'status') || !property_exists($orderStatus, 'statusOutput')) {
            throw new LocalizedException(__('Unknown payment status.'));
        }

        /** @var Payment\Transaction $transaction */
        $transaction = $this->getTransactionBy($transactionId);
        $objectClassName = get_class($orderStatus);
        $objectJson = $orderStatus->toJson();

        if ($transaction && $transaction->getId()) {
            $transaction->setAdditionalInformation(self::TRANSACTION_CLASS_KEY, $objectClassName);
            $transaction->setAdditionalInformation(self::TRANSACTION_INFO_KEY, $objectJson);
            $transaction->setAdditionalInformation(
                Payment\Transaction::RAW_DETAILS,
                $this->getVisibleInfo($orderStatus)
            );
            $this->transactionManager->updateTransaction($transaction);
        } else {
            // If transaction does not (yet) exist
            $payment->setTransactionAdditionalInfo(self::TRANSACTION_CLASS_KEY, $objectClassName);
            $payment->setTransactionAdditionalInfo(self::TRANSACTION_INFO_KEY, $objectJson);
            // setTransactionAdditionalInfo's doc block type hints are broken, but passing (string, array) works.
            $payment->setTransactionAdditionalInfo(
                Payment\Transaction::RAW_DETAILS,
                $this->getVisibleInfo($orderStatus)
            );
        }

        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_KEY, $orderStatus->status);
        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_CODE_KEY, $orderStatus->statusOutput->statusCode);
    }

    /**
     * @param AbstractOrderStatus|RefundResult|CaptureResponse|PaymentResponse $orderStatus
     * @return mixed[]
     */
    private function getVisibleInfo(
        AbstractOrderStatus $orderStatus
    ) {
        $visibleInfo = [];
        $visibleInfo['status'] = $orderStatus->status;

        $visibleInfo = array_merge(
            $visibleInfo,
            get_object_vars($orderStatus->statusOutput)
        );

        $visibleInfo = array_map(
            [$this, 'formatInfo'],
            $visibleInfo
        );

        $visibleInfo = array_filter($visibleInfo);

        return $visibleInfo;
    }

    /**
     * If the transaction is not found, this will return an empty transaction object or null.
     *
     * @param string $txnId
     * @return \Magento\Sales\Api\Data\TransactionInterface|null
     */
    public function getTransactionBy($txnId)
    {
        return $this->transactionManager->retrieveTransaction($txnId);
    }

    /**
     * Normalize values to be displayed in transaction info tab
     * @param mixed $info
     * @return mixed
     */
    public function formatInfo($info)
    {
        if (is_bool($info)) {
            $info = $info ? __('Yes') : __('No');
        } elseif (is_array($info)) {
            $info = implode(', ', array_map([$this, __FUNCTION__], $info));
        } elseif ($info instanceof APIError) {
            $info = $info->id;
        }
        return $info;
    }

    /**
     * Persists the transaction
     *
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     * @return void
     */
    public function save(\Magento\Sales\Api\Data\TransactionInterface $transaction)
    {
        $this->transactionManager->updateTransaction($transaction);
    }
}
