<?php

declare(strict_types=1);

namespace Worldline\Connect\Cron;

use Magento\Framework\Exception\LocalizedException;
use Worldline\Connect\Api\EventManagerInterface;
use Worldline\Connect\Model\Event\Processor;
use Worldline\Connect\Model\Event\Processor\SecondAttempts;

class ProcessEvents
{
    public function __construct(
        private readonly Processor $processor,
        private readonly SecondAttempts $secondAttemptsProcessor,
        private readonly EventManagerInterface $eventManager
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        // Before processing any batch, we have to ignore webhook events
        // from the RPP that had multiple attempts:
        foreach ($this->eventManager->findHostedCheckoutIdsInEvents() as $hostedCheckoutId) {
            $this->secondAttemptsProcessor->markOldAttemptsAsIgnored($hostedCheckoutId);
        }

        // Process regular batch:
        $this->processor->processBatch();
    }
}
