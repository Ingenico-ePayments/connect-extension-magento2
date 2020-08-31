<?php

declare(strict_types=1);

namespace Ingenico\Connect\Cron;

use Ingenico\Connect\Api\EventManagerInterface;
use Ingenico\Connect\Model\Event\Processor;
use Ingenico\Connect\Model\Event\Processor\SecondAttempts;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ProcessEvents
 *
 * Cron entry point to process events from the queue
 *
 * @package Ingenico\Connect\Cron
 */
class ProcessEvents
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var SecondAttempts
     */
    private $secondAttemptsProcessor;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * ProcessEvents constructor.
     *
     * @param Processor $processor
     * @param SecondAttempts $secondAttemptsProcessor
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        Processor $processor,
        SecondAttempts $secondAttemptsProcessor,
        EventManagerInterface $eventManager
    ) {
        $this->processor = $processor;
        $this->secondAttemptsProcessor = $secondAttemptsProcessor;
        $this->eventManager = $eventManager;
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
