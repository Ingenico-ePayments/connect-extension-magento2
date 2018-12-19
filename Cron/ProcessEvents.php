<?php
/**
 * See LICENSE.md for license details.
 */

namespace Netresearch\Epayments\Cron;

use Netresearch\Epayments\Model\Event\Processor;

/**
 * Class ProcessEvents
 *
 * Cron entry point to process events from the queue
 *
 * @package Netresearch\Epayments\Cron
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @link http://www.netresearch.de/
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
