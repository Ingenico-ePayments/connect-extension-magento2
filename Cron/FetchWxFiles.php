<?php

namespace Netresearch\Epayments\Cron;

use Magento\Store\Model\StoreManagerInterface;
use Netresearch\Epayments\Cron\FetchWxFiles\ProcessorInterface;

class FetchWxFiles
{
    /** @var ProcessorInterface */
    private $processor;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * FetchWxFiles constructor.
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
     * Load and process WX file for every website
     *
     * @param string $date
     * @return $this
     */
    public function execute($schedule)
    {
        foreach ($this->storeManager->getWebsites() as $website) {
            $group = $this->storeManager->getGroup($website->getDefaultGroupId());
            $this->processor->process($group->getDefaultStoreId());
        }

        return $this;
    }
}
