<?php
/**
 * See LICENSE.txt for license details.
 */

namespace Netresearch\Epayments\Model\Ingenico;

use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\ConfigInterface;

/**
 * Class MerchantReference
 *
 * @package Netresearch\Epayments\Model\Ingenico
 */
class MerchantReference
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * MerchantReference constructor.
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param OrderInterface $order
     * @return string
     */
    public function generateMerchantReference(OrderInterface $order)
    {
        return $this->config->getReferencePrefix() . $order->getIncrementId();
    }

    /**
     * @param string merchantReference
     * @throws \InvalidArgumentException
     * @return string
     */
    public function extractOrderReference($merchantReference)
    {
        if ($this->config->getReferencePrefix() !== ''
            && strpos($merchantReference, $this->config->getReferencePrefix()) !== 0) {
            // if there is a nonempty prefix set and it could not be found in the reference
            throw new \InvalidArgumentException('This reference is most likely not originating from this system.');
        }

        return str_replace($this->config->getReferencePrefix(), '', $merchantReference);
    }
}
