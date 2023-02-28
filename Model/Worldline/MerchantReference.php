<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline;

use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Order\IncrementIdService;

/**
 * Class MerchantReference
 *
 * @package Worldline\Connect\Model\Worldline
 */
class MerchantReference
{
    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    /**
     * @var IncrementIdService
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $incrementIdService;

    /**
     * MerchantReference constructor.
     *
     * @param ConfigInterface $config
     * @param IncrementIdService $incrementIdService
     */
    public function __construct(
        ConfigInterface $config,
        IncrementIdService $incrementIdService
    ) {
        $this->config = $config;
        $this->incrementIdService = $incrementIdService;
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function generateMerchantReferenceForOrder(OrderInterface $order)
    {
        return $order->getIncrementId();
    }

    public function validateMerchantReference(string $merchantReference): bool
    {
        $maxOrderIncrementIdLength = $this->incrementIdService->calculateMaxOrderIncrementIdLength();
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return strlen($merchantReference) + $maxOrderIncrementIdLength <= 30;
    }
}
