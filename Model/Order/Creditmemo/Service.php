<?php

namespace Ingenico\Connect\Model\Order\Creditmemo;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;

class Service implements ServiceInterface
{
    /**
     * @var CreditmemoInterface[]
     */
    private $creditmemos;

    /**
     * @var SearchCriteriaBuilder;
     */
    private $searchCriteriaBuilder;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * Service constructor.
     *
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     */
    public function __construct(
        SearchCriteriaBuilder $criteriaBuilder,
        CreditmemoRepositoryInterface $creditmemoRepository
    ) {
        $this->searchCriteriaBuilder = $criteriaBuilder;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->creditmemos = [];
    }

    /**
     * Retrieves the creditmemo that is currently being created or loads
     * the creditmemo via the transaction id from Ingenico.
     *
     * @param OrderPaymentInterface|Payment $payment
     * @param string $transactionId
     * @return CreditmemoInterface|Creditmemo
     * @throws NotFoundException
     */
    public function getCreditmemo(OrderPaymentInterface $payment, $transactionId = null)
    {
        if ($payment->getCreditmemo() !== null) {
            return $payment->getCreditmemo();
        }

        if ($transactionId !== null) {
            return $this->getCreditMemoByTxnId($transactionId);
        }

        throw new NotFoundException(
            __('No creditmemo for payment found.')
        );
    }

    /**
     * Try to fetch Creditmemo by its transaction id
     *
     * @param string $transactionId
     * @return CreditmemoInterface|Creditmemo
     * @throws NotFoundException
     */
    public function getCreditMemoByTxnId($transactionId)
    {
        if (!array_key_exists($transactionId, $this->creditmemos)) {
            $searchCriteria = $this->searchCriteriaBuilder->addFilter(
                'transaction_id',
                $transactionId
            );
            $searchCriteria->setPageSize(1);
            $creditmemos = $this->creditmemoRepository->getList($searchCriteria->create())->getItems();
            $creditmemo = array_shift($creditmemos);
            if ($creditmemo === null) {
                throw new NotFoundException(
                    __(
                        'No creditmemo for transaction id %transactionId found.',
                        ['transactionId' => $transactionId]
                    )
                );
            }
            $this->creditmemos[$transactionId] = $creditmemo;
        }
        return $this->creditmemos[$transactionId];
    }
}
