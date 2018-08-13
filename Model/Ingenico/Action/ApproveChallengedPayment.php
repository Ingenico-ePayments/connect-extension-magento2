<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigProvider;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__processchallenged_post
 */
class ApproveChallengedPayment extends AbstractAction implements ActionInterface
{
    /**
     * Accept payment
     *
     * @param Order $order
     * @throws LocalizedException
     */
    public function process(Order $order)
    {
        if (!$this->isIngenicoFraudOrder($order)) {
            throw new LocalizedException(
                __('This order was not placed via Ingenico ePayments or was not detected as fraud')
            );
        }

        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        $paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        $response = $this->ingenicoClient->ingenicoPaymentAccept($paymentId, $order->getStoreId());

        $payment->setIsTransactionClosed(false);
        $this->postProcess($payment, $response);
    }

    /**
     * Check if order was marked as fraud by Ingenico
     *
     * @param Order $order
     * @return bool
     */
    private function isIngenicoFraudOrder(Order $order)
    {
        return $this->isIngenicoOrder($order) && $order->getStatus() === Order::STATUS_FRAUD;
    }

    /**
     * Check if is ingenico order
     *
     * @param Order $order
     * @return bool
     */
    private function isIngenicoOrder(Order $order)
    {
        /** @var MethodInterface $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        return $paymentMethod->getCode() == ConfigProvider::CODE;
    }
}
