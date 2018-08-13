<?php

namespace Netresearch\Epayments\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Logger\Monolog;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\ResourceModel\IngenicoStaleOrder\CollectionFactory;
use Netresearch\Epayments\Model\ResourceModel\IngenicoStaleOrder\Collection;

class CancelStaleOrders
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Monolog
     */
    private $logger;

    /**
     * CancelStaleOrders constructor.
     *
     * @param ConfigInterface $config
     * @param OrderRepositoryInterface $orderRepository
     * @param CollectionFactory $orderCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Monolog $logger
     */
    public function __construct(
        ConfigInterface $config,
        OrderRepositoryInterface $orderRepository,
        CollectionFactory $orderCollectionFactory,
        StoreManagerInterface $storeManager,
        Monolog $logger
    ) {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Cancel pending orders which are a certain age (configured in module settings).
     * Called via cron each day at 5a.m.
     */
    public function execute()
    {
        $stores = $this->storeManager->getStores(true);
        foreach ($stores as $store) {
            $cancelationPeriod = $this->config->getPendingOrdersCancellationPeriod($store->getId());
            /** @var Collection $collection */
            $collection = $this->orderCollectionFactory->create();
            $collection->addCreatedAtFilter($cancelationPeriod, $store->getId());
            /** @var Order $order */
            foreach ($collection->getItems() as $order) {
                $this->cancelOrder($order);
            }
            $collection->save();
        }

        return $this;
    }

    /**
     * Try to cancel the order on the platform and/or in Magento.
     *
     * @param Order $order
     */
    private function cancelOrder(Order $order)
    {
        $this->logger->info('Cancelling order '.$order->getIncrementId());

        /**
         * Online invalidation of the payment through the payment object
         */
        try {
            $order->getPayment()->cancel();
            $this->logger->info('Cancelled order on platform: '.$order->getIncrementId());
        } catch (\Ingenico\Connect\Sdk\ResponseException $exception) {
            $this->logger->err(
                'Could not cancel order on platform due to error: '.$exception->getMessage()
            );
        }

        /**
         * Actual Magento order cancellation
         */
        try {
            $order->registerCancellation('Automatic order cancellation');
            $this->logger->info('Cancelled Magento order: '.$order->getIncrementId());
        } catch (LocalizedException $exception) {
            $this->logger->err(
                'Could not cancel order automatically: '.$exception->getMessage()
            );
        }
    }
}
