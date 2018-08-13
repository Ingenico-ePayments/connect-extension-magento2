<?php

namespace Netresearch\Epayments\Cron\FetchWxFiles;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Logger\Monolog;
use Magento\Sales\Api\OrderRepositoryInterface;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;
use Netresearch\Epayments\Model\StatusResponseManager;

class StatusUpdateResolver implements StatusUpdateResolverInterface
{
    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaBuilderFactory;

    /**
     * @var Monolog
     */
    private $logger;
    /**
     * @var StatusResponseManager
     */
    private $statusResponseManager;

    /**
     * StatusUpdateResolver constructor.
     * @param ResolverInterface $resolver
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilderFactory $criteriaBuilderFactory
     * @param Monolog $logger
     * @param StatusResponseManager $statusResponseManager
     */
    public function __construct(
        ResolverInterface $resolver,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilderFactory $criteriaBuilderFactory,
        Monolog $logger,
        StatusResponseManager $statusResponseManager
    ) {
        $this->statusResolver = $resolver;
        $this->orderRepository = $orderRepository;
        $this->criteriaBuilderFactory = $criteriaBuilderFactory;
        $this->logger = $logger;
        $this->statusResponseManager = $statusResponseManager;
    }

    /**
     * @param AbstractOrderStatus[] $statusList [OrderIncrementId => AbstractOrderStatus]
     * @return string[]                         [OrderEntityId => OrderIncrementId]
     */
    public function resolveBatch($statusList)
    {
        $updatedOrders = [];
        $criteriaBuilder = $this->criteriaBuilderFactory->create();
        $criteriaBuilder->addFilter('increment_id', array_keys($statusList), 'in');
        $orderList = $this->orderRepository->getList($criteriaBuilder->create());
        foreach ($orderList->getItems() as $order) {
            try {
                /** @var RefundResult|PaymentResponse $ingenicoOrderStatus */
                $ingenicoOrderStatus = $statusList[$order->getIncrementId()];
                $currentStatus = $this->statusResponseManager->get($order->getPayment(), $ingenicoOrderStatus->id);
                if (!$currentStatus || $currentStatus->status !== $ingenicoOrderStatus->status) {
                    $this->statusResolver->resolve($order, $ingenicoOrderStatus);
                    $this->orderRepository->save($order);
                    $updatedOrders[$order->getEntityId()] = $order->getIncrementId();
                }
            } catch (\Exception $e) {
                $message = sprintf("Error occured for order %s: %s", $order->getIncrementId(), $e->getMessage());
                $this->logger->addError($message);
            }
        }

        return $updatedOrders;
    }
}
