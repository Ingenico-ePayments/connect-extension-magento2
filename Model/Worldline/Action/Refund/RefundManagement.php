<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Worldline\Connect\Api\RefundManagementInterface;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\RequestBuilder\Refund\ApproveRefundRequestBuilder;

class RefundManagement implements RefundManagementInterface
{
    /**
     * @var ClientInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $worldlineClient;

    /**
     * @var Config
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    /**
     * @var ApproveRefundRequestBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $approveRefundRequestBuilder;

    public function __construct(
        ClientInterface $worldlineClient,
        Config $config,
        ApproveRefundRequestBuilder $approveRefundRequestBuilder
    ) {
        $this->worldlineClient = $worldlineClient;
        $this->config = $config;
        $this->approveRefundRequestBuilder = $approveRefundRequestBuilder;
    }

    public function fetchRefundStatus(CreditmemoInterface $creditMemo): RefundResult
    {
        return $this->worldlineClient
            ->getWorldlineClient($creditMemo->getStoreId())
            ->merchant($this->config->getMerchantId($creditMemo->getStoreId()))
            ->refunds()
            ->get($creditMemo->getTransactionId());
    }

    public function approveRefund(CreditmemoInterface $creditMemo): void
    {
        try {
            $request = $this->approveRefundRequestBuilder->build($creditMemo);
            $this->worldlineClient->worldlineRefundAccept(
                $creditMemo->getTransactionId(),
                $request,
                $creditMemo->getStoreId()
            );
        } catch (ResponseException $exception) {
            throw new LocalizedException(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __('Error while trying to approve the refund: %1', $exception->getMessage()),
                $exception
            );
        }
    }
}
