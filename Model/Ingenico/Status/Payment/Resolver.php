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
     * @param Payment $status
     * @throws LocalizedException
     * @throws NotFoundException
     */
    public function resolve(OrderInterface $order, Payment $status)
    {
        if (!$this->isStatusNewerThanPreviousStatus($order, $status)) {
            return;
        }

        $this->preparePayment($order->getPayment(), $status);
        $statusHandler = $this->statusHandlerPool->get($status->status);
        $statusHandler->resolveStatus($order, $status);

        $this->updateStatusCodeChangeDate($order, $status);
        $this->updateStatus($order, $status);
        $this->updatePayment($order->getPayment(), $status);
    }
}
