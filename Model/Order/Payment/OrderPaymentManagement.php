<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Order\Payment;

use DateTime;
use LogicException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Worldline\Connect\Api\OrderPaymentManagementInterface;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\StatusResponseManagerInterface;

class OrderPaymentManagement implements OrderPaymentManagementInterface
{
    public const KEY_PAYMENT_STATUS = 'payment_status';
    public const KEY_PAYMENT_STATUS_CODE_CHANGE_DATE_TIME = 'payment_status_code_change_date_time';
    public const KEY_REFUND_STATUS = 'refund_status';
    public const KEY_REFUND_STATUS_CODE_CHANGE_DATE_TIME = 'refund_status_code_change_date_time';

    /**
     * @var DateTimeFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $dateTimeFactory;

    /**
     * @var StatusResponseManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusResponseManager;

    /**
     * OrderPaymentManagement constructor.
     *
     * @param DateTimeFactory $dateTimeFactory
     * @param StatusResponseManagerInterface $statusResponseManager This is only used for legacy orders, where the
     *     Worldline object is saved in the transaction
     */
    public function __construct(DateTimeFactory $dateTimeFactory, StatusResponseManagerInterface $statusResponseManager)
    {
        $this->dateTimeFactory = $dateTimeFactory;
        $this->statusResponseManager = $statusResponseManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getWorldlinePaymentStatus(OrderPaymentInterface $payment): string
    {
        // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine
        if (($status = $this->getAdditionalInformation($payment, Config::PAYMENT_STATUS_KEY)) ||
            ($status = $this->getLegacyProperty($payment, 'status'))
        ) {
            return $status;
        }

        throw new LogicException('No payment status found on payment object');
    }

    /**
     * {@inheritDoc}
     */
    public function getWorldlineRefundStatus(OrderPaymentInterface $payment): string
    {
        // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine
        if (($status = $this->getAdditionalInformation($payment, self::KEY_REFUND_STATUS)) ||
            ($status = $this->getLegacyProperty($payment, 'status'))
        ) {
            return $status;
        }

        throw new LogicException('No refund status found on payment object');
    }

    /**
     * {@inheritDoc}
     */
    public function getWorldlinePaymentStatusCodeChangeDate(OrderPaymentInterface $payment): DateTime
    {
        // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine
        if (($dateTime = $this->getAdditionalInformation($payment, self::KEY_PAYMENT_STATUS_CODE_CHANGE_DATE_TIME)) ||
            ($dateTime = $this->getLegacyProperty($payment, 'statusOutput', 'statusCodeChangeDateTime'))
        ) {
            return $this->dateTimeFactory->create($dateTime);
        }

        throw new LogicException('No payment status code change date/time found on payment object');
    }

    /**
     * {@inheritDoc}
     */
    public function getWorldlineRefundStatusCodeChangeDate(OrderPaymentInterface $payment): DateTime
    {
        // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine
        if (($dateTime = $this->getAdditionalInformation($payment, self::KEY_REFUND_STATUS_CODE_CHANGE_DATE_TIME)) ||
            ($dateTime = $this->getLegacyProperty($payment, 'statusOutput', 'statusCodeChangeDateTime'))
        ) {
            return $this->dateTimeFactory->create($dateTime);
        }

        throw new LogicException('No refund status code change date/time found on payment object');
    }

    private function getAdditionalInformation(OrderPaymentInterface $payment, string $key): ?string
    {
        $additionalInformation = $payment->getAdditionalInformation();
        return $additionalInformation[$key] ?? null;
    }

    private function getLegacyProperty(
        OrderPaymentInterface $payment,
        string $key,
        // phpcs:ignore SlevomatCodingStandard.TypeHints.NullableTypeForNullDefaultValue.NullabilityTypeMissing
        string $nestedKey = null
    ): ?string {
        if ($status = $this->statusResponseManager->get($payment, $payment->getLastTransId())) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            if (property_exists($status, $key)) {
                if ($nestedKey === null) {
                    return $status->{$key};
                }

                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                if (property_exists($status->{$key}, $nestedKey)) {
                    return $status->{$key}->{$nestedKey};
                }
            }
        }
        return null;
    }
}
