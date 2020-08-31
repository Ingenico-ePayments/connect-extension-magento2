<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico;

use Ingenico\Connect\Model\Order\IncrementIdService;
use InvalidArgumentException;
use Magento\Sales\Api\Data\CreditmemoInterface;
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

    /**
     * @param CreditmemoInterface $creditMemo
     * @return string
     */
    public function generateMerchantReferenceForCreditMemo(CreditmemoInterface $creditMemo)
    {
        return $this->config->getReferencePrefix() . $creditMemo->getIncrementId();
    }

    /**
     * @param string merchantReference
     * @return string
     * @throws InvalidArgumentException
     */
    public function extractOrderReference($merchantReference)
    {
        if ($this->config->getReferencePrefix() !== ''
            && strpos($merchantReference, $this->config->getReferencePrefix()) !== 0) {
            // if there is a nonempty prefix set and it could not be found in the reference
            throw new InvalidArgumentException('This reference is most likely not originating from this system.');
        }

        return str_replace($this->config->getReferencePrefix(), '', $merchantReference);
    }

    public function validateMerchantReference(string $merchantReference): bool
    {
        $maxOrderIncrementIdLength = $this->incrementIdService->calculateMaxOrderIncrementIdLength();
        return strlen($merchantReference) + $maxOrderIncrementIdLength <= 30;
    }
}
