<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Ingenico\Connect\Helper\Data;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;

class PendingFraudApproval extends AbstractHandler implements HandlerInterface
{
    public const LEGACY_EVENT_NAME = 'ingenico_fraud';
    protected const EVENT_STATUS = 'pending_fraud_approval';

    /**
     * @var ConfigInterface
     */
    private $epaymentsConfig;

    /**
     * PendingFraudApproval constructor.
     *
     * @param ConfigInterface $epaymentsConfig
     */
    public function __construct(ConfigInterface $epaymentsConfig)
    {
        $this->epaymentsConfig = $epaymentsConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, IngenicoPayment $ingenicoStatus)
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

        $this->dispatchEvent($order, $ingenicoStatus);

        // Also dispatch legacy event to support backward compatibility for 3rd party vendors:
        $this->eventManager->dispatch(self::LEGACY_EVENT_NAME);
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
