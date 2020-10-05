<?php

declare(strict_types=1);

namespace Ingenico\Connect\Api;

interface RefundManagementInterface
{
    /**
     * Fetch the refund status of a credit memo from the remote API.
     *
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo
     * @return \Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult
     * @api
     */
    public function fetchRefundStatus(
        \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo
    ): \Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;

    /**
     * Approve the refund on the remote API.
     *
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     */
    public function approveRefund(
        \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo
    ): void;
}
