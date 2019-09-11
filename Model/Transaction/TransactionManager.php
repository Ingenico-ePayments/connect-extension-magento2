<?php

namespace Ingenico\Connect\Model\Transaction;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
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
     * @return \Magento\Sales\Api\Data\TransactionInterface[]
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
     * Persists the transaction
     *
     * @param $transaction
     */
    public function updateTransaction($transaction)
    {
        $this->transactionRepository->save($transaction);
    }
}
