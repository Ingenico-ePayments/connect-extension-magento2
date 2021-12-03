<?php
/**
 * See LICENSE.md for license details.
 */

namespace Ingenico\Connect\Gateway;

use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\ConfigProvider;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;

use function array_key_exists;
use function in_array;

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
     * @param mixed[] $subject
     * @param int|null $storeId
     *
     * @return bool
     */
    public function handle(array $subject, $storeId = null)
    {
        if (!array_key_exists('payment', $subject)) {
            return $this->handleInitialize($subject, $storeId);
        }

        $payment = $subject['payment']->getPayment();
        if (!$payment instanceof Payment) {
            return $this->handleInitialize($subject, $storeId);
        }

        if (!$payment->getAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH)) {
            return $this->handleInitialize($subject, $storeId);
        }

        return false;
    }

    /**
     * @param mixed[] $subject
     * @param int|null $storeId
     *
     * @return bool
     */
    private function handleInitialize(array $subject, $storeId = null)
    {
        $paymentProductId =
            $subject['payment']->getPayment()->getAdditionalInformation(Config::PRODUCT_PAYMENT_METHOD_KEY) === 'card'
                ? 'cards' : $subject['payment']->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);

        if (in_array($paymentProductId, ConfigProvider::LOCKED_INLINE_PAYMENT_PRODUCTS, false)) {
            return false;
        }

        if (!array_key_exists($paymentProductId, ConfigProvider::CONFIGURABLE_INLINE_PAYMENT_PRODUCTS)) {
            return true;
        }
        return $this->config->getPaymentProductCheckoutType(
            ConfigProvider::CONFIGURABLE_INLINE_PAYMENT_PRODUCTS[$paymentProductId],
            $storeId
        ) === Config::CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT;
    }
}
