<?php

declare(strict_types=1);

namespace Ingenico\Connect\Api;

use DateTime;
use LogicException;
use Magento\Sales\Api\Data\OrderPaymentInterface;

interface OrderPaymentManagementInterface
{
    /**
     * Get last known payment status received from the Ingenico API.
     * This is the information that is stored in the order payment object
     * and does not do a new API call.
     *
     * @param OrderPaymentInterface $payment
     * @return string
     * @throws LogicException
     * @api
     */
    public function getIngenicoPaymentStatus(OrderPaymentInterface $payment): string;

    /**
     * Get last known refund status received from the Ingenico API.
     * This is the information that is stored in the order payment object
     * and does not do a new API call.
     *
     * @param OrderPaymentInterface $payment
     * @return string
     * @throws LogicException
     * @api
     */
    public function getIngenicoRefundStatus(OrderPaymentInterface $payment): string;

    /**
     * Get last known payment status code change datetime.
     *
     * @param OrderPaymentInterface $payment
     * @return DateTime
     * @throws LogicException
     * @api
     */
    public function getIngenicoPaymentStatusCodeChangeDate(OrderPaymentInterface $payment): DateTime;

    /**
     * Get last known refund status code change datetime.
     *
     * @param OrderPaymentInterface $payment
     * @return DateTime
     * @throws LogicException
     * @api
     */
    public function getIngenicoRefundStatusCodeChangeDate(OrderPaymentInterface $payment): DateTime;
}
