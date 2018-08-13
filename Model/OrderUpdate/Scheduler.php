<?php

namespace Netresearch\Epayments\Model\OrderUpdate;

use Magento\Framework\Logger\Monolog;
use Magento\Sales\Model\Order as MagentoOrder;

class Scheduler implements SchedulerInterface
{
    /** @var Monolog */
    private $logger;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    private $dateTime;

    /** @var array */
    private $intervalSetMinute = [
        30,
        105,
        120,
    ];

    /**
     * @param Monolog $logger
     */
    public function __construct(
        Monolog $logger,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->logger = $logger;
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function timeForAttempt(MagentoOrder $order)
    {
        /** @var int $intervalLastAttempt */
        $intervalLastAttempt = $this->getInterval($order, $order->getPullPaymentLastAttemptTime());

        /** @var int $intervalCurrent */
        $intervalCurrent = $this->getInterval($order, $this->dateTime->timestamp(), false);

        return $intervalCurrent > $intervalLastAttempt;
    }

    /**
     * {@inheritdoc}
     */
    public function timeForWr(MagentoOrder $order)
    {
        return $this->getInterval($order, $this->dateTime->timestamp()) == count($this->intervalSetMinute);
    }

    /**
     * Detect interval by timestamp
     *
     * @param MagentoOrder $order
     * @param $timestamp
     * @param bool $skipLogging
     * @return int
     */
    private function getInterval(
        MagentoOrder $order,
        $timestamp,
        $skipLogging = true
    ) {
        // build timestamp from date created
        $orderDateCreatedTimestamp = $this->dateTime->timestamp($order->getCreatedAt());

        if (!$skipLogging) {
            $this->logger->info(
                "minutes passed from creation "
                . floor(($this->dateTime->timestamp() - $orderDateCreatedTimestamp) / 60)
                . " (interval " . implode(", ", $this->intervalSetMinute) . ")"
            );
        }

        // detect interval
        $interval = 0;
        foreach ($this->intervalSetMinute as $point) {
            $currentPosition = floor(($timestamp - $orderDateCreatedTimestamp) / 60);
            if ($currentPosition >= $point) {
                $interval++;
            }
        }

        if (!$skipLogging) {
            $this->logger->info("interval is $interval");
        }

        return $interval;
    }
}
