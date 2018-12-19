<?php
/**
 * See LICENSE.md for license details.
 */

namespace Netresearch\Epayments\Gateway;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;

class CanInitialize implements ValueHandlerInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * CanInitialize constructor.
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * This command handles whether we need to initialize the hosted checkout. The rules are:
     *
     * - "Hosted Checkout" and "Direct Hosted Checkout" always need initialization
     * - "Inline" checkout needs initialization when no payload was given.
     *
     * @param mixed[] $subject
     * @param int|null $storeId
     *
     * @return bool
     */
    public function handle(array $subject, $storeId = null)
    {
        $shouldInitialize = true;
        $isInline = $this->config->getCheckoutType($storeId) === Config::CONFIG_INGENICO_CHECKOUT_TYPE_INLINE;

        if ($isInline) {
            /** @var Payment $payment */
            $payment = $subject['payment']->getPayment();
            $shouldInitialize = !$payment->getAdditionalInformation(Config::CLIENT_PAYLOAD_KEY);
        }

        return $shouldInitialize;
    }
}
