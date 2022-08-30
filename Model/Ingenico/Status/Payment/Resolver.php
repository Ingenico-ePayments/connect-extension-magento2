<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment;

use Ingenico\Connect\Model\Ingenico\Status\AbstractResolver;
use Ingenico\Connect\Model\Order\Payment\OrderPaymentManagement;
use Ingenico\Connect\Model\StatusResponseManagerInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\OrderInterface;

class Resolver extends AbstractResolver implements ResolverInterface
{
    protected const KEY_STATUS = OrderPaymentManagement::KEY_PAYMENT_STATUS;
    protected const KEY_STATUS_CODE_CHANGE_DATE_TIME = OrderPaymentManagement::KEY_PAYMENT_STATUS_CODE_CHANGE_DATE_TIME;

    /**
     * @var PoolInterface
     */
    private $statusHandlerPool;

    public function __construct(
        StatusResponseManagerInterface $statusResponseManager,
        PoolInterface $statusHandlerPool
    ) {
        parent::__construct($statusResponseManager);
        $this->statusHandlerPool = $statusHandlerPool;
    }

    /**
     * @param OrderInterface $order
     * @param Payment $payment
     * @throws LocalizedException
     * @throws NotFoundException
     */
    public function resolve(OrderInterface $order, Payment $payment)
    {
        if (!$this->isStatusNewerThanPreviousStatus($order, $payment)) {
            return;
        }

        $this->preparePayment($order->getPayment(), $payment);

        // Only run the resolver on an actual status change, otherwise
        // only update the meta-data:
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        $currentStatus = $additionalInformation[self::KEY_STATUS] ?? null;
        if ($payment->status !== $currentStatus) {
            $statusHandler = $this->statusHandlerPool->get($payment->status);
            $statusHandler->resolveStatus($order, $payment);
        }

        $this->updateStatusCodeChangeDate($order, $payment);
        $this->updateStatus($order, $payment);
        $this->updatePayment($order->getPayment(), $payment);
    }
}
