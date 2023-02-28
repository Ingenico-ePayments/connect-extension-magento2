<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\OrderUpdate;

use Magento\Sales\Model\Order as MagentoOrder;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface SchedulerInterface
{
    /**
     * Decide if it's time to pull payment from worldline
     *
     * @param Order $order
     * @return bool
     */
    // phpcs:ignore Squiz.WhiteSpace.FunctionSpacing.After
    public function timeForAttempt(MagentoOrder $order);


    /**
     * Decide if it's time for WR
     *
     * @param Order $order
     * @return bool
     */
    public function timeForWr(MagentoOrder $order);
}
