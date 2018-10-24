<?php

namespace Netresearch\Epayments\Model\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderService implements OrderServiceInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaBuilderFactory;

    /**
     * OrderService constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilderFactory $criteriaBuilderFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilderFactory $criteriaBuilderFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->criteriaBuilderFactory = $criteriaBuilderFactory;
    }

    /**
     * @param int $incrementId
     * @return OrderInterface
     */
    public function getByIncrementId($incrementId)
    {
        $criteriaBuilder = $this->criteriaBuilderFactory->create();
        $criteriaBuilder->addFilter('increment_id', $incrementId);
        $orderList = $this->orderRepository->getList($criteriaBuilder->create())->getItems();

        return array_shift($orderList);
    }
}
