<?php

namespace Ingenico\Connect\Model;

use Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Errors\Definitions\APIError;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;

/**
 * Class StatusResponseManager
 *
 * @package Ingenico\Connect\Model
 * @deprecated
 */
class StatusResponseManager implements StatusResponseManagerInterface
{
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * StatusResponseManager constructor.
     *
     * @param TransactionRepository $transactionRepository
     */
    public function __construct(
        TransactionRepository $transactionRepository
    ) {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Retrieve last PaymentResponse object stored in transaction additionalInformation. It contains canonical
     * information about a payment, such as isCancellable or isRefundable and isAuthorized values.
     *
     * @param InfoInterface|Payment $payment
     * @param string $transactionId
     * @return IngenicoPayment|false
     * @deprecated This kind of information needs to be stored on the transaction, not on the payment object. Use
     *     \Ingenico\Connect\Model\Transaction\TransactionManager::getResponseDataFromTransaction() instead
     */
    public function get(InfoInterface $payment, $transactionId)
    {
        $orderStatus = false;
        /** @var Transaction $transaction */
        $transaction = $this->transactionRepository
            ->getByTransactionId($transactionId, $payment->getId(), $payment->getOrder()->getId());

        if ($transaction && $classPath = $transaction->getAdditionalInformation(self::TRANSACTION_CLASS_KEY)) {
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
     * @deprecated This kind of information needs to be stored on the transaction, not on the payment object. Use
     *     \Ingenico\Connect\Model\Transaction\TransactionManager::setResponseDataOnTransaction() instead.
     */
    public function set(
        InfoInterface $payment,
        $transactionId,
        AbstractOrderStatus $orderStatus
    ) {
        if (!property_exists($orderStatus, 'status') || !property_exists($orderStatus, 'statusOutput')) {
            throw new LocalizedException(__('Unknown payment status.'));
        }

        /** @var Transaction $transaction */
        $transaction = $this->transactionRepository
            ->getByTransactionId($transactionId, $payment->getId(), $payment->getOrder()->getId());

        if ($transaction && $transaction->getId()) {
            $this->setResponseDataOnTransaction($orderStatus, $transaction);
            $payment->getOrder()->addRelatedObject($transaction);
        } else {
            $objectClassName = get_class($orderStatus);
            $objectJson = $orderStatus->toJson();
            // If transaction does not (yet) exist
            $payment->setTransactionAdditionalInfo(self::TRANSACTION_CLASS_KEY, $objectClassName);
            $payment->setTransactionAdditionalInfo(self::TRANSACTION_INFO_KEY, $objectJson);
            // setTransactionAdditionalInfo's doc block type hints are broken, but passing (string, array) works.
            $payment->setTransactionAdditionalInfo(
                Transaction::RAW_DETAILS,
                $this->getVisibleInfo($orderStatus)
            );
        }

        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_KEY, $orderStatus->status);
        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_CODE_KEY, $orderStatus->statusOutput->statusCode);
    }

    /**
     * @param AbstractOrderStatus $responseData
     * @param Transaction $transaction
     * @throws LocalizedException
     * @deprecated Use the one on to the TransactionManager instead
     */
    public function setResponseDataOnTransaction(AbstractOrderStatus $responseData, Transaction $transaction)
    {
        $objectClassName = get_class($responseData);
        $objectJson = $responseData->toJson();
        $transaction->setAdditionalInformation(self::TRANSACTION_CLASS_KEY, $objectClassName);
        $transaction->setAdditionalInformation(self::TRANSACTION_INFO_KEY, $objectJson);
        $transaction->setAdditionalInformation(
            Transaction::RAW_DETAILS,
            $this->getVisibleInfo($responseData)
        );
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
     * @deprecated This kind of information needs to be stored on the transaction, not on the payment object
     */
    public function getTransactionBy($transactionId, InfoInterface $payment)
    {
        return $this->transactionRepository
            ->getByTransactionId($transactionId, $payment->getId(), $payment->getOrder()->getId());
    }

    /**
     * Normalize values to be displayed in transaction info tab
     *
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
     * @deprecated Use the save()-method of the TransactionManager instead
     */
    public function save(\Magento\Sales\Api\Data\TransactionInterface $transaction)
    {
        $this->transactionRepository->save($transaction);
    }
}
