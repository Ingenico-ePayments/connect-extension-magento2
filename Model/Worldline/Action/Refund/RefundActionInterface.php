<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action\Refund;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Worldline\Connect\Model\Worldline\Action\ActionInterface;

/**
 * All Refund actions implement this interface.
 * Since it are actions, persistence needs to be done inside their
 * implementations.
 *
 * @package Worldline\Connect\Model\Worldline\Action\Refund
 */
// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface RefundActionInterface extends ActionInterface
{
    /**
     * @param CreditmemoInterface $creditMemo
     * @return void
     * @throws LocalizedException
     */
    public function process(CreditmemoInterface $creditMemo);
}
