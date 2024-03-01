<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Order\EmailManagerFraud;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;

class PendingFraudApproval extends AbstractHandler implements HandlerInterface
{
    public const LEGACY_EVENT_NAME = 'worldline_fraud';
    protected const EVENT_STATUS = 'pending_fraud_approval';

    /**
     * @var EmailManagerFraud
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $emailManagerFraud;

    public function __construct(
        ManagerInterface $eventManager,
        ConfigInterface $config,
        EmailManagerFraud $emailManagerFraud
    ) {
        parent::__construct($eventManager, $config);
        $this->emailManagerFraud = $emailManagerFraud;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(Order $order, Payment $status)
    {
        /** @var OrderPayment $orderPayment */
        $orderPayment = $order->getPayment();

        /**
         * Set magic data fields on payment:
         * is_transaction_pending: sets the order in payment_review state and any invoices into pending state
         * is_fraud_detected: sets order into suspected_fraud status
         * is_transaction_closed: prevents the magento payment transaction from closing, relevent for void actions
         */
        $orderPayment->setIsTransactionPending(true);
        $orderPayment->setIsFraudDetected(true);
        $orderPayment->setIsTransactionClosed(false);

        // register capture or authorization notification for hosted checkout, do nothing for inline payments
        $this->registerPaymentNotification($order, $order->getBaseGrandTotal());

        $this->emailManagerFraud->process($order);

        $this->dispatchEvent($order, $status);

        // Also dispatch legacy event to support backward compatibility for 3rd party vendors:
        $this->eventManager->dispatch(self::LEGACY_EVENT_NAME);
    }

    /**
     * Perform payment action registration on payment object depending on config settings.
     * Client payload key is only set when inline checkout is configured for a specific payment product.
     *
     * @param OrderInterface $order
     * @param $amount
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    private function registerPaymentNotification(OrderInterface $order, $amount)
    {
        /** @var OrderPayment $payment */
        $payment = $order->getPayment();
        try {
            if (!$payment->getAdditionalInformation(Config::CLIENT_PAYLOAD_KEY)) {
                /**
                 * Only register something, if we have some sort of hosted checkout, otherwise we are in inline flow
                 * and Magento will handle the workflow itself.
                 *
                 * @see Payment::place()
                 */
                if ($payment->getMethodInstance()->getConfigPaymentAction() === AbstractMethod::ACTION_AUTHORIZE) {
                    $payment->registerCaptureNotification($amount);
                } else {
                    $payment->registerAuthorizationNotification($amount);
                }
            }
            // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (LocalizedException $e) {
        }
    }
}
