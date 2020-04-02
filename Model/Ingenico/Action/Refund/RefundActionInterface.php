<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Model\Ingenico\Action\ActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 * All Refund actions implement this interface.
 * Since it are actions, persistence needs to be done inside their
 * implementations.
 *
 * @package Ingenico\Connect\Model\Ingenico\Action\Refund
 */
interface RefundActionInterface extends ActionInterface
{
    /**
     * @param CreditmemoInterface $creditMemo
     * @return void
     * @throws LocalizedException
     */
    public function process(CreditmemoInterface $creditMemo);
}
