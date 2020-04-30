<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderService implements OrderServiceInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    /**
     * @param string $incrementId
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    public function getByIncrementId(string $incrementId): OrderInterface
    {
        return $this->getOrder(
            $this->searchCriteriaBuilder
                ->addFilter(OrderInterface::INCREMENT_ID, $incrementId)
                ->create()
        );
    }

    /**
     * @param string $hostedCheckoutId
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    public function getByHostedCheckoutId(string $hostedCheckoutId): OrderInterface
    {
        $payment = $this->getOrderPayment(
            $this->searchCriteriaBuilder
                ->addFilter(
                    OrderPaymentInterface::ADDITIONAL_INFORMATION,
                    sprintf('%%"ingenico_hosted_checkout_id":"%1$s"%%', $hostedCheckoutId),
                    'like'
                )
                ->create()
        );

        return $this->orderRepository->get($payment->getParentId());
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    private function getOrder(SearchCriteriaInterface $searchCriteria): OrderInterface
    {
        $orderList = $this->orderRepository->getList($searchCriteria);
        if ($orderList->getTotalCount() === 0) {
            $this->throwException($searchCriteria);
        }

        $orders = $orderList->getItems();
        return $orders[key($orders)];
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderPaymentInterface
     * @throws NoSuchEntityException
     */
    private function getOrderPayment(SearchCriteriaInterface $searchCriteria): OrderPaymentInterface
    {
        $orderPaymentList = $this->orderPaymentRepository->getList($searchCriteria);

        if ($orderPaymentList->getTotalCount() === 0) {
            $this->throwException($searchCriteria);
        }

        $orderPayments = $orderPaymentList->getItems();
        return $orderPayments[key($orderPayments)];
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @throws NoSuchEntityException
     */
    private function throwException(SearchCriteriaInterface $searchCriteria)
    {
        $filterGroups = $searchCriteria->getFilterGroups();
        $searchName = $filterGroups[0]->getFilters()[0]->getField();
        $searchValue = $filterGroups[0]->getFilters()[0]->getValue();
        throw new NoSuchEntityException(__('No order found with %1: %2', $searchName, $searchValue));
    }
}
