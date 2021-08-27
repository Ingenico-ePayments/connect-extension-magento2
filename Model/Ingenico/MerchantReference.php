<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico;

use Ingenico\Connect\Model\Order\IncrementIdService;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Model\ConfigInterface;

/**
 * Class MerchantReference
 *
 * @package Ingenico\Connect\Model\Ingenico
 */
class MerchantReference
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var IncrementIdService
     */
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
        return $this->config->getReferencePrefix() . $order->getIncrementId();
    }

    public function validateMerchantReference(string $merchantReference): bool
    {
        $maxOrderIncrementIdLength = $this->incrementIdService->calculateMaxOrderIncrementIdLength();
        return strlen($merchantReference) + $maxOrderIncrementIdLength <= 30;
    }
}
