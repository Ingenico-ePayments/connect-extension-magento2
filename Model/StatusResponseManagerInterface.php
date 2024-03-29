<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as WorldlinePayment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Interface StatusResponseManagerInterface
 *
 * @package Worldline\Connect\Model
 */
// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface StatusResponseManagerInterface
{
    /**
     * @deprecated Use those of the TransactionManager instead
     */
    public const TRANSACTION_INFO_KEY = 'gc_response_object';

    /**
     * @deprecated Use those of the TransactionManager instead
     */
    public const TRANSACTION_CLASS_KEY = 'gc_response_class';

    /**
     * Retrieve last PaymentResponse object stored in transaction additionalInformation. It contains canonical
     * information about a payment, such as isCancellable or isRefundable and isAuthorized values.
     *
     * @param InfoInterface|Payment $payment
     * @param string $transactionId
     * @return WorldlinePayment|false
     * @deprecated
     */
    public function get(InfoInterface $payment, $transactionId);

    /**
     * Update the PaymentResponse object stored in a transaction.
     *
     * @param InfoInterface|Payment $payment
     * @param $transactionId
     * @param AbstractOrderStatus $orderStatus
     * @throws LocalizedException
     * @deprecated
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function set(InfoInterface $payment, $transactionId, AbstractOrderStatus $orderStatus);

    /**
     * Serialize response data and store it on a transaction
     *
     * @param AbstractOrderStatus $responseData
     * @param Transaction $transaction
     * @return null
     * @deprecated
     */
    public function setResponseDataOnTransaction(AbstractOrderStatus $responseData, Transaction $transaction);

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * If the transaction is not found, this will return an empty transaction object or null.
     *
     * @param string $transactionId
     * @return \Magento\Sales\Api\Data\TransactionInterface|null
     * @deprecated
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function getTransactionBy($transactionId, \Magento\Payment\Model\InfoInterface $payment);

    /**
     * Normalize values to be displayed in transaction info tab
     *
     * @param mixed $info
     * @return mixed
     */
    public function formatInfo($info);

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Persists the transaction
     *
     * @param \Magento\Sales\Api\Data\TransactionInterface $transaction
     * @return void
     * @deprecated
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function save(\Magento\Sales\Api\Data\TransactionInterface $transaction);
}
