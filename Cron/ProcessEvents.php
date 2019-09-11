<?php
/**
 * See LICENSE.md for license details.
 */

namespace Ingenico\Connect\Cron;

use Ingenico\Connect\Model\Event\Processor;

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
     * ProcessEvents constructor.
     *
     * @param Processor $processor
     */
    public function __construct(Processor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $this->processor->processBatch();
    }
}
