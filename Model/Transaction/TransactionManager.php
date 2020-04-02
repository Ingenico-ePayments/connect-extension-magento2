<?php

namespace Ingenico\Connect\Model\Transaction;

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
 * @package Ingenico\Connect\Model\Transaction
 */
class TransactionManager
{
    const TRANSACTION_INFO_KEY = 'gc_response_object';
    const TRANSACTION_CLASS_KEY = 'gc_response_class';

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var FilterBuilder
     */
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

    /**
     * @param string $txnId
     * @return Transaction|null
     */
    public function retrieveTransaction($txnId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('txn_id', $txnId);
        $searchCriteria->setPageSize(1);
        $transactions = $this->transactionRepository->getList($searchCriteria->create())->getItems();

        return array_shift($transactions);
    }

    /**
     * @param Payment $payment
     * @return TransactionInterface[]
     */
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
        if (!array_key_exists(self::TRANSACTION_CLASS_KEY, $additionalInformation) ||
            !array_key_exists(self::TRANSACTION_INFO_KEY, $additionalInformation)
        ) {
            throw new LocalizedException(__('No response data set on transaction'));
        }

        $objectName = $additionalInformation[self::TRANSACTION_CLASS_KEY];
        $object = new $objectName();
        if (!$object instanceof AbstractOrderStatus) {
            throw new LocalizedException(__('Invalid object type'));
        }

        $objectData = $additionalInformation[self::TRANSACTION_INFO_KEY];
        $object->fromJson($objectData);

        return $object;
    }

    /**
     * @param AbstractOrderStatus $orderStatus
     * @return mixed[]
     */
    private function getVisibleInfo(AbstractOrderStatus $orderStatus)
    {
        $visibleInfo = [];
        $json = json_decode($orderStatus->toJson(), true);

        if (array_key_exists('status', $json)) {
            $visibleInfo['status'] = $json['status'];
        }

        if (array_key_exists('statusOutput', $json)) {
            $visibleInfo = array_merge(
                $visibleInfo,
                $json['statusOutput']
            );
        }

        $visibleInfo = array_map(
            function ($info) {
                if (is_bool($info)) {
                    $info = $info ? 'Yes' : 'No';
                } elseif (is_array($info)) {
                    $info = implode(', ', array_map([$this, __FUNCTION__], $info));
                } elseif ($info instanceof APIError) {
                    $info = $info->id;
                }

                return $info;
            },
            $visibleInfo
        );

        $visibleInfo = array_filter($visibleInfo);

        return $visibleInfo;
    }

    /**
     * Persists the transaction
     *
     * @param $transaction
     */
    public function updateTransaction($transaction)
    {
        $this->transactionRepository->save($transaction);
    }
}
