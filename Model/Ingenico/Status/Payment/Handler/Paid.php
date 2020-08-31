<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment\Handler;

use Ingenico\Connect\Model\Ingenico\Status\Payment\HandlerInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Model\StatusResponseManager;

class Paid extends AbstractHandler implements HandlerInterface
{
    protected const EVENT_STATUS = 'paid';

    /**
     * @var CapturedFactory
     */
    private $capturedFactory;

    /**
     * @var ConfigInterface
     */
    private $ePaymentsConfig;

    /**
     * @var StatusResponseManager
     */
    private $statusResponseManager;

    /**
     * Paid constructor.
     *
     * @param CapturedFactory $capturedFactory
     * @param ConfigInterface $ePaymentsConfig
     * @param StatusResponseManager $statusResponseManager
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        CapturedFactory $capturedFactory,
        ConfigInterface $ePaymentsConfig,
        StatusResponseManager $statusResponseManager,
        ManagerInterface $eventManager
    ) {
        parent::__construct($eventManager);
        $this->capturedFactory = $capturedFactory;
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->statusResponseManager = $statusResponseManager;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveStatus(OrderInterface $order, Payment $ingenicoStatus)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $currentPaymentStatus = '';
        $captureTransaction = $this->statusResponseManager->getTransactionBy($ingenicoStatus->id, $payment);
        if ($captureTransaction !== null) {
            $currentCaptureStatus = $this->statusResponseManager->get($payment, $ingenicoStatus->id);
            $currentPaymentStatus = $currentCaptureStatus->status;
        }

        if ($currentPaymentStatus !== StatusInterface::CAPTURED) {
            /** @var Captured $capturedHandler */
            $capturedHandler = $this->capturedFactory->create();
            $capturedHandler->resolveStatus($order, $ingenicoStatus);
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
