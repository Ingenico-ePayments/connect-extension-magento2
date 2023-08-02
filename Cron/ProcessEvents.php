<?php

declare(strict_types=1);

namespace Worldline\Connect\Cron;

use Magento\Framework\Exception\LocalizedException;
use Worldline\Connect\Model\Event\Processor;

class ProcessEvents
{
    public function __construct(
        private readonly Processor $processor,
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->processor->processBatch();
    }
}
