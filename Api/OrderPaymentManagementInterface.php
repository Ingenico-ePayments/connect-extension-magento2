<?php

declare(strict_types=1);

namespace Worldline\Connect\Api;

use DateTime;
use LogicException;
use Magento\Sales\Api\Data\OrderPaymentInterface;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface OrderPaymentManagementInterface
{
    /**
     * Get last known payment status received from the Worldline API.
     * This is the information that is stored in the order payment object
     * and does not do a new API call.
     *
     * @param OrderPaymentInterface $payment
     * @return string
     * @throws LogicException
     * @api
     */
    public function getWorldlinePaymentStatus(OrderPaymentInterface $payment): string;

    /**
     * Get last known refund status received from the Worldline API.
     * This is the information that is stored in the order payment object
     * and does not do a new API call.
     *
     * @param OrderPaymentInterface $payment
     * @return string
     * @throws LogicException
     * @api
     */
    public function getWorldlineRefundStatus(OrderPaymentInterface $payment): string;

    /**
     * Get last known payment status code change datetime.
     *
     * @param OrderPaymentInterface $payment
     * @return DateTime
     * @throws LogicException
     * @api
     */
    public function getWorldlinePaymentStatusCodeChangeDate(OrderPaymentInterface $payment): DateTime;

    /**
     * Get last known refund status code change datetime.
     *
     * @param OrderPaymentInterface $payment
     * @return DateTime
     * @throws LogicException
     * @api
     */
    public function getWorldlineRefundStatusCodeChangeDate(OrderPaymentInterface $payment): DateTime;
}
