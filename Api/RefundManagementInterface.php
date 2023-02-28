<?php

declare(strict_types=1);

namespace Worldline\Connect\Api;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface RefundManagementInterface
{
    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Fetch the refund status of a credit memo from the remote API.
     *
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo
     * @return \Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult
     * @api
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function fetchRefundStatus(
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    ): \Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Approve the refund on the remote API.
     *
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function approveRefund(
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        \Magento\Sales\Api\Data\CreditmemoInterface $creditMemo
    ): void;
}
