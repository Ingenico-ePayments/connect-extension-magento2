<?php

namespace Ingenico\Connect\Model\OrderUpdate;

use Magento\Framework\Logger\Monolog;
use Magento\Store\Model\StoreManagerInterface;

class Processor implements ProcessorInterface
{
    /** @var OrderInterface */
    private $order;

    /** @var Monolog */
    private $logger;

    /** @var \Ingenico\Connect\Model\ResourceModel\IngenicoPendingOrder\CollectionFactory */
    private $collectionFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * Processor constructor.
     *
     * @param OrderInterface $order
     * @param Monolog $logger
     * @param \Ingenico\Connect\Model\ResourceModel\IngenicoPendingOrder\CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        OrderInterface $order,
        Monolog $logger,
        \Ingenico\Connect\Model\ResourceModel\IngenicoPendingOrder\CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->order = $order;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Update order statuses
     *
     * @param string $scopeId
     */
    public function process($scopeId)
    {
        // get target orders
        $orderCollection = $this->collectionFactory->create();
        $orderCollection->addRealTimeUpdateOrdersFilter($scopeId);

        $total = $orderCollection->getSize();
        if ($total > 0) {
            $this->logger->info("--- PROCESSOR STARTED FOR STORE ID $scopeId ---");
            $this->logger->info("selected $total orders");

            foreach ($orderCollection as $orderPayment) {
                $this->order->process($orderPayment);
            }

            $this->logger->info("All orders were processed");
        }
    }
}
