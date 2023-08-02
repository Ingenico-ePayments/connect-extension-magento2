<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment\Handler;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Worldline\Status\Payment\HandlerInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;

class Paid extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'paid';

    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $ePaymentsConfig;

    /**
     * @var StatusResponseManager
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusResponseManager;

    /**
     * @var Config
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        parent::__construct($eventManager, $ePaymentsConfig);
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->statusResponseManager = $statusResponseManager;
        $this->orderConfig = $orderConfig;
    }

    /**
     * {@inheritDoc}
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function resolveStatus(OrderInterface $order, Payment $worldlineStatus)
    {
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        $currentPaymentStatus = '';
        $captureTransaction = $this->statusResponseManager->getTransactionBy($worldlineStatus->id, $payment);
        if ($captureTransaction !== null) {
            $currentCaptureStatus = $this->statusResponseManager->get($payment, $worldlineStatus->id);
            $currentPaymentStatus = $currentCaptureStatus->status;
        }

        // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine
        if ($currentPaymentStatus !== StatusInterface::CAPTURED &&
            $currentPaymentStatus !== StatusInterface::CAPTURE_REQUESTED
        ) {
            if ($order->getState() === Order::STATE_PAYMENT_REVIEW && $order->getStatus() === Order::STATUS_FRAUD) {
                $payment->setIsTransactionApproved(true);
                $payment->update(false);
            }

            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionClosed(true);
            $payment->registerCaptureNotification($order->getBaseGrandTotal());

            if ($captureTransaction === null) {
                $payment->setNotificationResult(true);
            }
        }

        // phpcs:ignore Generic.Commenting.Todo.TaskFound
        // @todo: remove hard dependency on Order model:
        if ($order instanceof Order && $order->getInvoiceCollection()) {
            foreach ($order->getInvoiceCollection() as $invoice) {
                /**
                 * Check if an invoice with the same transaction id exists
                 */
                // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine
                if ($invoice->getTransactionId() === $worldlineStatus->id
                    && (int) $invoice->getState() !== Order\Invoice::STATE_PAID
                ) {

                    /**
                     * Let the payment update the invoice state and the order totals by performing an arbitrary update
                     * operation
                     */
                    $payment->setTransactionId($worldlineStatus->id);
                    $payment->setIsTransactionApproved(true);
                    $payment->update(false);
                }
            }
        }

        $this->dispatchEvent($order, $worldlineStatus);
    }
}
