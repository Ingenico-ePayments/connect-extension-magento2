<?php
/**
 * For license details see LICENSE.txt
 */

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Helper\Data;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Order\EmailManagerFraud;

class PendingFraudApproval implements HandlerInterface
{
    /**
     * @var EmailManagerFraud
     */
    private $fraudEmailManager;

    /**
     * @var ConfigInterface
     */
    private $epaymentsConfig;

    /**
     * PendingFraudApproval constructor.
     *
     * @param EmailManagerFraud $fraudEmailManager
     * @param ConfigInterface $epaymentsConfig
     */
    public function __construct(EmailManagerFraud $fraudEmailManager, ConfigInterface $epaymentsConfig)
    {
        $this->fraudEmailManager = $fraudEmailManager;
        $this->epaymentsConfig = $epaymentsConfig;
    }

    /**
     * @param OrderInterface|Order $order
     * @param AbstractOrderStatus|IngenicoPayment $ingenicoStatus
     */
    public function resolveStatus(OrderInterface $order, AbstractOrderStatus $ingenicoStatus)
    {
        $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
        $amount = Data::reformatMagentoAmount($amount);

        /** @var Payment $payment */
        $payment = $order->getPayment();

        /**
         * Set magic data fields on payment:
         * is_transaction_pending: sets the order in payment_review state and any invoices into pending state
         * is_fraud_detected: sets order into suspected_fraud status
         * is_transaction_closed: prevents the magento payment transaction from closing, relevent for void actions
         */
        $payment->setIsTransactionPending(true);
        $payment->setIsFraudDetected(true);
        $payment->setIsTransactionClosed(false);

        // register capture or authorization notification for hosted checkout, do nothing for inline payments
        $this->registerPaymentNotification($order, $amount);

        $this->fraudEmailManager->process($order);
    }

    /**
     * Perform payment action registration on payment object depending on config settings
     *
     * @param OrderInterface $order
     * @param $amount
     */
    private function registerPaymentNotification(OrderInterface $order, $amount)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        $checkoutType = $this->epaymentsConfig->getCheckoutType($order->getStoreId());
        $captureMode = $this->epaymentsConfig->getCaptureMode($order->getStoreId());
        if ($checkoutType !== Config::CONFIG_INGENICO_CHECKOUT_TYPE_INLINE) {
            /**
             * Only register something, if we have some sort of hosted checkout, otherwise we are in inline flow
             * and Magento will handle the workflow itself.
             *
             * @see Payment::place()
             */
            if ($captureMode === Config::CONFIG_INGENICO_CAPTURES_MODE_DIRECT) {
                $payment->registerCaptureNotification($amount);
            } else {
                $payment->registerAuthorizationNotification($amount);
            }
        }
    }
}
