<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Helper\Data;
use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture as IngenicoCapture;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Model\StatusResponseManager;
use Magento\Sales\Model\Order\Config;

class Paid extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'paid';

    /**
     * @var ConfigInterface
     */
    private $ePaymentsConfig;

    /**
     * @var StatusResponseManager
     */
    private $statusResponseManager;

    /**
     * @var Config
     */
    private $orderConfig;

    /**
     * Paid constructor.
     *
     * @param ConfigInterface $ePaymentsConfig
     * @param StatusResponseManager $statusResponseManager
     * @param Config $orderConfig
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ConfigInterface $ePaymentsConfig,
        StatusResponseManager $statusResponseManager,
        Config $orderConfig,
        ManagerInterface $eventManager
    ) {
        parent::__construct($eventManager);
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->statusResponseManager = $statusResponseManager;
        $this->orderConfig = $orderConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        $currentPaymentStatus = '';
        $captureTransaction = $this->statusResponseManager->getTransactionBy($ingenicoStatus->id, $payment);
        if ($captureTransaction !== null) {
            $currentCaptureStatus = $this->statusResponseManager->get($payment, $ingenicoStatus->id);
            $currentPaymentStatus = $currentCaptureStatus->status;
        }

        if ($currentPaymentStatus !== StatusInterface::CAPTURED) {
            if ($ingenicoStatus instanceof IngenicoPayment) {
                $amount = $ingenicoStatus->paymentOutput->amountOfMoney->amount;
            } elseif ($ingenicoStatus instanceof IngenicoCapture) {
                $amount = $ingenicoStatus->captureOutput->amountOfMoney->amount;
            } else {
                throw new LocalizedException(__('Unknown order status.'));
            }

            if ($order->getState() === Order::STATE_PAYMENT_REVIEW && $order->getStatus() === Order::STATUS_FRAUD) {
                $payment->setIsTransactionApproved(true);
                $payment->update(false);
            }

            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionClosed(true);
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus($this->orderConfig->getStateDefaultStatus(Order::STATE_PROCESSING));
            $payment->registerCaptureNotification(Data::reformatMagentoAmount($amount));

            if ($captureTransaction === null) {
                $payment->setNotificationResult(true);
            }
        }

        // @todo: remove hard dependency on Order model:
        if ($order instanceof Order) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                /**
                 * Check if an invoice with the same transaction id exists
                 */
                if ($invoice->getTransactionId() === $ingenicoStatus->id
                    && (int) $invoice->getState() !== Order\Invoice::STATE_PAID
                ) {

                    /**
                     * Let the payment update the invoice state and the order totals by performing an arbitrary update
                     * operation
                     */
                    $payment->setTransactionId($ingenicoStatus->id);
                    $payment->setIsTransactionApproved(true);
                    $payment->update(false);
                }
            }
        }

        $this->dispatchEvent($order, $ingenicoStatus);
    }
}
