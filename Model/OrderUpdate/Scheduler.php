<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\OrderUpdate;

use Magento\Framework\Logger\Monolog;
use Magento\Sales\Model\Order as MagentoOrder;

class Scheduler implements SchedulerInterface
{
    /** @var Monolog */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /** @var \Magento\Framework\Stdlib\DateTime\DateTime */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $dateTime;

    /** @var array */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
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
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName, SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
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
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    private function getInterval(
        MagentoOrder $order,
        $timestamp,
        $skipLogging = true
    ) {
        // build timestamp from date created
        $orderDateCreatedTimestamp = $this->dateTime->timestamp($order->getCreatedAt());

        if (!$skipLogging) {
            $this->logger->info(
                // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired
                "minutes passed from creation "
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                . floor(($this->dateTime->timestamp() - $orderDateCreatedTimestamp) / 60)
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName, Squiz.Strings.DoubleQuoteUsage.NotRequired
                . " (interval " . implode(", ", $this->intervalSetMinute) . ")"
            );
        }

        // detect interval
        $interval = 0;
        foreach ($this->intervalSetMinute as $point) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $currentPosition = floor(($timestamp - $orderDateCreatedTimestamp) / 60);
            if ($currentPosition >= $point) {
                $interval++;
            }
        }

        if (!$skipLogging) {
            // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
            $this->logger->info("interval is $interval");
        }

        return $interval;
    }
}
