<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline;

use Magento\Sales\Api\Data\OrderInterface;

// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface StatusInterface
{
    public const STATUSES = [
        self::REDIRECTED,
        self::PENDING_PAYMENT,
        self::PENDING_COMPLETION,
        self::ACCOUNT_VERIFIED,
        self::PENDING_FRAUD_APPROVAL,
        self::AUTHORIZATION_REQUESTED,
        self::PENDING_APPROVAL,
        self::PENDING_CAPTURE,
        self::CAPTURE_REQUESTED,
        self::CAPTURED,
        self::PAID,
        self::REVERSED,
        self::CHARGEBACK,
        self::REJECTED,
        self::REJECTED_CAPTURE,
        self::CANCELLED,
        self::REFUND_REQUESTED,
        self::REFUNDED,
    ];

    public const APPROVED_STATUSES = [
        StatusInterface::CAPTURE_REQUESTED,
        StatusInterface::CAPTURED,
        StatusInterface::PAID,
    ];

    public const DENIED_STATUSES = [
        StatusInterface::REVERSED,
        StatusInterface::CHARGEBACK,
        StatusInterface::REJECTED,
        StatusInterface::REJECTED_CAPTURE,
        StatusInterface::CANCELLED,
    ];

    public const REDIRECTED = 'REDIRECTED';
    public const PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const PENDING_COMPLETION = 'PENDING_COMPLETION';
    public const ACCOUNT_VERIFIED = 'ACCOUNT_VERIFIED';
    public const PENDING_FRAUD_APPROVAL = 'PENDING_FRAUD_APPROVAL';
    public const AUTHORIZATION_REQUESTED = 'AUTHORIZATION_REQUESTED';
    public const PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const PENDING_CAPTURE = 'PENDING_CAPTURE';
    public const CAPTURE_REQUESTED = 'CAPTURE_REQUESTED';
    public const CAPTURED = 'CAPTURED';
    public const PAID = 'PAID';
    public const REVERSED = 'REVERSED';
    public const CHARGEBACK = 'CHARGEBACKED';
    public const REJECTED = 'REJECTED';
    public const REJECTED_CAPTURE = 'REJECTED_CAPTURE';
    public const CANCELLED = 'CANCELLED';
    public const REFUND_REQUESTED = 'REFUND_REQUESTED';
    public const REFUNDED = 'REFUNDED';

    // below are statuses for payout/bank refunds
    public const PAYOUT_REQUESTED = 'PAYOUT_REQUESTED';
    public const ACCOUNT_CREDITED = 'ACCOUNT_CREDITED';
    public const REJECTED_CREDIT = 'REJECTED_CREDIT';

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Get status code
     *
     * @return string
     */
    public function getStatusCode();

    /**
     * Apply status to order
     *
     * @param OrderInterface $order
     * @return void
     */
    public function apply(OrderInterface $order);
}
