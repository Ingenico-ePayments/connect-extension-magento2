<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Order\Payment;

use DateTime;
use LogicException;
use Magento\Sales\Api\Data\OrderPaymentInterface;

interface OrderPaymentManagementInterface
{
    /**
     * @param OrderPaymentInterface $payment
     * @return string
     * @throws LogicException
     */
    public function getIngenicoPaymentStatus(OrderPaymentInterface $payment): string;

    /**
     * @param OrderPaymentInterface $payment
     * @return string
     * @throws LogicException
     */
    public function getIngenicoRefundStatus(OrderPaymentInterface $payment): string;

    /**
     * @param OrderPaymentInterface $payment
     * @return DateTime
     * @throws LogicException
     */
    public function getIngenicoPaymentStatusCodeChangeDate(OrderPaymentInterface $payment): DateTime;

    /**
     * @param OrderPaymentInterface $payment
     * @return DateTime
     * @throws LogicException
     */
    public function getIngenicoRefundStatusCodeChangeDate(OrderPaymentInterface $payment): DateTime;
}
