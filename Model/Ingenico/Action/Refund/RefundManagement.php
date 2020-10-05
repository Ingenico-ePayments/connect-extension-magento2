<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Api\RefundManagementInterface;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund\ApproveRefundRequestBuilder;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;

class RefundManagement implements RefundManagementInterface
{
    /**
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ApproveRefundRequestBuilder
     */
    private $approveRefundRequestBuilder;

    public function __construct(
        ClientInterface $ingenicoClient,
        Config $config,
        ApproveRefundRequestBuilder $approveRefundRequestBuilder
    ) {
        $this->ingenicoClient = $ingenicoClient;
        $this->config = $config;
        $this->approveRefundRequestBuilder = $approveRefundRequestBuilder;
    }

    public function fetchRefundStatus(CreditmemoInterface $creditMemo): RefundResult
    {
        return $this->ingenicoClient
            ->getIngenicoClient($creditMemo->getStoreId())
            ->merchant($this->config->getMerchantId($creditMemo->getStoreId()))
            ->refunds()
            ->get($creditMemo->getTransactionId());
    }

    public function approveRefund(CreditmemoInterface $creditMemo): void
    {
        try {
            $request = $this->approveRefundRequestBuilder->build($creditMemo);
            $this->ingenicoClient->ingenicoRefundAccept(
                $creditMemo->getTransactionId(),
                $request,
                $creditMemo->getStoreId()
            );
        } catch (ResponseException $exception) {
            throw new LocalizedException(
                __('Error while trying to approve the refund: %1', $exception->getMessage()),
                $exception
            );
        }
    }
}
