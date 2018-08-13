<?php

namespace Netresearch\Epayments\Model\Ingenico\GlobalCollect;

use Magento\Framework\Exception\InputException;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;

class StatusMapper
{
    private static $statusMap = [
        ProductMapper::METHOD_CARDS => [
            StatusInterface::REDIRECTED => [20, 25, 30, 50, 650],
            StatusInterface::ACCOUNT_VERIFIED => [300, 350],
            StatusInterface::PENDING_COMPLETION => [60, 200],
            StatusInterface::PENDING_FRAUD_APPROVAL => [525],
            StatusInterface::PENDING_APPROVAL => [600],
            StatusInterface::PENDING_CAPTURE => [680],
            StatusInterface::AUTHORIZATION_REQUESTED => [625],
            StatusInterface::CAPTURE_REQUESTED => [800, 900, 925, 975],
            StatusInterface::REFUND_REQUESTED => [800, 900, 925, 975],
            StatusInterface::PAID => [1000, 1030, 1050],
            StatusInterface::CHARGEBACK => [1500],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100, 130, 150, 160, 170, 172, 175, 180, 220, 230, 280, 310, 320, 330],
            StatusInterface::REJECTED_CAPTURE => [190, 1100, 1120, 1150, 1850],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_DIRECT_DEBITS => [
            StatusInterface::REDIRECTED => [20, 25, 30],
            StatusInterface::PENDING_FRAUD_APPROVAL => [525],
            StatusInterface::PENDING_APPROVAL => [600],
            StatusInterface::REFUND_REQUESTED => [800, 900, 975, 1020],
            StatusInterface::PAID => [1000, 1010, 1050],
            StatusInterface::REVERSED => [1510, 1520],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100, 160],
            StatusInterface::REJECTED_CAPTURE => [1100, 1210],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_EWALLET => [
            StatusInterface::REDIRECTED => [20, 25, 30, 50, 150, 650],
            StatusInterface::PENDING_PAYMENT => [1020],
            StatusInterface::ACCOUNT_VERIFIED => [300],
            StatusInterface::PENDING_COMPLETION => [70],
            StatusInterface::PENDING_FRAUD_APPROVAL => [525],
            StatusInterface::PENDING_APPROVAL => [600],
            StatusInterface::PENDING_CAPTURE => [680],
            StatusInterface::CAPTURED => [800, 900, 975],
            StatusInterface::PAID => [1000, 1050],
            StatusInterface::REVERSED => [1520],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100, 120, 125, 130, 160],
            StatusInterface::REJECTED_CAPTURE => [1100],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_REALTIME_BT_EINVOICE => [
            StatusInterface::REDIRECTED => [20, 25, 30, 50, 150, 650],
            StatusInterface::PENDING_PAYMENT => [1020],
            StatusInterface::PENDING_APPROVAL => [600],
            StatusInterface::CAPTURED => [800, 900, 975],
            StatusInterface::PAID => [1000, 1050],
            StatusInterface::REVERSED => [1520],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100, 120, 125, 130, 140],
            StatusInterface::REJECTED_CAPTURE => [1100],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_PREPAID => [
            StatusInterface::REDIRECTED => [20, 25, 30, 50, 150, 650],
            StatusInterface::PENDING_PAYMENT => [1020],
            StatusInterface::CAPTURED => [800, 900, 975],
            StatusInterface::PAID => [1000, 1050],
            StatusInterface::REVERSED => [1520],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100, 130],
            StatusInterface::REJECTED_CAPTURE => [1100, 1120],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_MOBILE => [
            StatusInterface::REDIRECTED => [20, 25, 30, 50, 150, 650],
            StatusInterface::PENDING_PAYMENT => [1020],
            StatusInterface::CAPTURED => [800, 900],
            StatusInterface::PAID => [1000, 1050],
            StatusInterface::REVERSED => [1520],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100, 125],
            StatusInterface::REJECTED_CAPTURE => [1100],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_CASH => [
            StatusInterface::REDIRECTED => [20, 25, 30, 50],
            StatusInterface::PENDING_PAYMENT => [55, 65, 1020],
            StatusInterface::CAPTURED => [800, 900],
            StatusInterface::PAID => [1000, 1050],
            StatusInterface::REVERSED => [1520],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100],
            StatusInterface::REJECTED_CAPTURE => [1100],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_BANKTRANSFER => [
            StatusInterface::REDIRECTED => [20, 25, 30],
            StatusInterface::PENDING_PAYMENT => [800, 900, 1020],
            StatusInterface::PENDING_APPROVAL => [600],
            StatusInterface::PAID => [1000, 1050],
            StatusInterface::REVERSED => [1520],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100],
            StatusInterface::REJECTED_CAPTURE => [1100],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_CHECKS_INVOICE => [
            StatusInterface::PENDING_PAYMENT => [800, 900, 950, 1020],
            StatusInterface::PENDING_CAPTURE => [680],
            StatusInterface::CAPTURED => [1000, 1050],
            StatusInterface::REVERSED => [1250, 1520],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100],
            StatusInterface::REJECTED_CAPTURE => [1100],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_PAYOUTS => [
            StatusInterface::PENDING_APPROVAL => [600],
            StatusInterface::PAYOUT_REQUESTED => [800, 900, 975],
            StatusInterface::ACCOUNT_CREDITED => [2000, 2030],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100],
            StatusInterface::REJECTED_CREDIT => [2100, 2110, 2120, 2130],
            StatusInterface::CANCELLED => [400, 99999]
        ],
        ProductMapper::METHOD_BANK_REFUNDS => [
            StatusInterface::PENDING_APPROVAL => [600],
            StatusInterface::REFUND_REQUESTED => [800, 900, 1810],
            StatusInterface::REFUNDED => [1800],
            StatusInterface::REJECTED => [100],
            StatusInterface::REJECTED_CAPTURE => [1100],
            StatusInterface::CANCELLED => [400, 99999]
        ],
    ];

    /**
     * @param $gcStatusCode
     * @param $gcPaymentGroupId
     * @return array
     * @throws InputException
     */
    public static function getConnectStatus($gcStatusCode, $gcPaymentGroupId)
    {
        if (!array_key_exists($gcPaymentGroupId, self::$statusMap)) {
            throw new InputException(__('Payment group id not supported: %id', ['id' => $gcPaymentGroupId]));
        }
        $groupStatuses = self::$statusMap[$gcPaymentGroupId];
        $availableStatuses = array_filter(
            $groupStatuses,
            function ($element) use ($gcStatusCode) {
                return array_search($gcStatusCode, $element) !== false;
            }
        );
        return array_keys($availableStatuses);
    }
}
