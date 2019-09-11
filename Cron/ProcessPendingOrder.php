<?php

namespace Ingenico\Connect\Cron;

use Magento\Store\Model\StoreManagerInterface;
use Ingenico\Connect\Model\OrderUpdate\ProcessorInterface;

class ProcessPendingOrder
{
    /** @var ProcessorInterface */
    private $processor;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * ProcessPendingOrder constructor.
     *
     * @param ProcessorInterface $processor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProcessorInterface $processor,
        StoreManagerInterface $storeManager
    ) {
        $this->processor = $processor;
        $this->storeManager = $storeManager;
    }

    /**
     * Process pending ingenico orders
     *
     * @return $this
     */
    public function execute()
    {
        foreach ($this->storeManager->getStores(true) as $store) {
            $this->processor->process($store->getId());
        }

        return $this;
    }
}
