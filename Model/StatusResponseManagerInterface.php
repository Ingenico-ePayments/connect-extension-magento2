<?php

namespace Netresearch\Epayments\Model;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Interface StatusResponseManagerInterface
 *
 * @package Netresearch\Epayments\Model
 */
interface StatusResponseManagerInterface
{
    const TRANSACTION_INFO_KEY = 'gc_response_object';

    const TRANSACTION_CLASS_KEY = 'gc_response_class';

    /**
     * Retrieve last PaymentResponse object stored in transaction additionalInformation. It contains canonical
     * information about a payment, such as isCancellable or isRefundable and isAuthorized values.
     *
     * @param InfoInterface|Payment $payment
     * @param string $transactionId
     * @return IngenicoPayment|false
     */
    public function get(InfoInterface $payment, $transactionId);

    /**
     * Update the PaymentResponse object stored in a transaction.
     *
     * @param InfoInterface|Payment $payment
     * @param $transactionId
     * @param AbstractOrderStatus $orderStatus
     * @throws LocalizedException
     */
    public function set(InfoInterface $payment, $transactionId, AbstractOrderStatus $orderStatus);

    /**
     * If the transaction is not found, this will return an empty transaction object or null.
     *
     * @param string $txnId
     * @return \Magento\Sales\Api\Data\TransactionInterface|null
     */
    public function getTransactionBy($txnId);

    /**
     * Normalize values to be displayed in transaction info tab
     *
     * @param mixed $info
     * @return mixed
     */
    public function formatInfo($info);

    /**
     * Persists the transaction
     *
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     * @return void
     */
    public function save(\Magento\Sales\Api\Data\TransactionInterface $transaction);
}
