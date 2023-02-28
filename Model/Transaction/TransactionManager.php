<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Transaction;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Errors\Definitions\APIError;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class TransactionManager
 *
 * @package Worldline\Connect\Model\Transaction
 */
class TransactionManager implements TransactionManagerInterface
{
    public const TRANSACTION_INFO_KEY = 'gc_response_object';
    public const TRANSACTION_CLASS_KEY = 'gc_response_class';

    /**
     * @var SearchCriteriaBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $searchCriteriaBuilder;

    /**
     * @var TransactionRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $transactionRepository;

    /**
     * @var FilterBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $filterBuilder;

    /**
     * TransactionManager constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionRepositoryInterface $transactionRepository
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionRepositoryInterface $transactionRepository,
        FilterBuilder $filterBuilder
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->filterBuilder = $filterBuilder;
    }

    public function retrieveTransaction(string $txnId): ?TransactionInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('txn_id', $txnId);
        $searchCriteria->setPageSize(1);
        $transactions = $this->transactionRepository->getList($searchCriteria->create())->getItems();

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return array_shift($transactions);
    }

    // phpcs:disable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @param Payment $payment
     * @return TransactionInterface[]
     */
    // phpcs:enable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    public function retrieveTransactions($payment)
    {
        $filters[] = $this->filterBuilder->setField('payment_id')
            ->setValue($payment->getId())
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters);
        $transactionList = $this->transactionRepository->getList($searchCriteria->create());

        return $transactionList->getItems();
    }

    /**
     * @param AbstractOrderStatus $responseData
     * @param TransactionInterface $transaction
     * @throws LocalizedException
     */
    public function setResponseDataOnTransaction(
        AbstractOrderStatus $responseData,
        TransactionInterface $transaction
    ) {
        // phpcs:ignore SlevomatCodingStandard.Classes.ModernClassNameReference.ClassNameReferencedViaFunctionCall, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
     * @param TransactionInterface $transaction
     * @return AbstractOrderStatus
     * @throws LocalizedException
     */
    public function getResponseDataFromTransaction(
        TransactionInterface $transaction
    ): AbstractOrderStatus {
        $additionalInformation = $transaction->getAdditionalInformation();
        // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (!array_key_exists(self::TRANSACTION_CLASS_KEY, $additionalInformation) ||
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            !array_key_exists(self::TRANSACTION_INFO_KEY, $additionalInformation)
        ) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('No response data set on transaction'));
        }

        $objectName = $additionalInformation[self::TRANSACTION_CLASS_KEY];
        $object = new $objectName();
        if (!$object instanceof AbstractOrderStatus) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Invalid object type'));
        }

        $objectData = $additionalInformation[self::TRANSACTION_INFO_KEY];
        $object->fromJson($objectData);

        return $object;
    }

    // phpcs:disable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @param AbstractOrderStatus $orderStatus
     * @return mixed[]
     */
    // phpcs:enable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    private function getVisibleInfo(AbstractOrderStatus $orderStatus)
    {
        $visibleInfo = [];
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $json = json_decode($orderStatus->toJson(), true);

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (array_key_exists('status', $json)) {
            $visibleInfo['status'] = $json['status'];
        }

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (array_key_exists('statusOutput', $json)) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $visibleInfo = array_merge(
                $visibleInfo,
                $json['statusOutput']
            );
        }

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $visibleInfo = array_map(
            function ($info) {
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                if (is_bool($info)) {
                    $info = $info ? 'Yes' : 'No';
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                } elseif (is_array($info)) {
                    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                    $info = implode(', ', array_map([$this, __FUNCTION__], $info));
                } elseif ($info instanceof APIError) {
                    $info = $info->id;
                }

                return $info;
            },
            $visibleInfo
        );

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $visibleInfo = array_filter($visibleInfo);

        return $visibleInfo;
    }

    /**
     * Persists the transaction
     *
     * @param $transaction
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function updateTransaction($transaction)
    {
        $this->transactionRepository->save($transaction);
    }
}
