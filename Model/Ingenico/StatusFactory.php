<?php

namespace Ingenico\Connect\Model\Ingenico;

use Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Magento\Framework\ObjectManagerInterface;
use Ingenico\Connect\Model\Ingenico\Status\Refund\RefundHandlerInterface;

class StatusFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * StatusFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @var array
     */
    private static $paymentStatusToClassPathMap = [
        StatusInterface::REDIRECTED              => 'Redirected',
        StatusInterface::PENDING_PAYMENT         => 'PendingPayment',
        StatusInterface::ACCOUNT_VERIFIED        => 'NullStatus',
        StatusInterface::PENDING_FRAUD_APPROVAL  => 'PendingFraudApproval',
        StatusInterface::AUTHORIZATION_REQUESTED => 'AuthorizationRequested',
        StatusInterface::PENDING_APPROVAL        => 'PendingApproval',
        StatusInterface::PENDING_CAPTURE         => 'PendingCapture',
        StatusInterface::CAPTURE_REQUESTED       => 'CaptureRequested',
        StatusInterface::CAPTURED                => 'Captured',
        StatusInterface::PAID                    => 'Paid',
        StatusInterface::REVERSED                => 'RejectedCapture',
        StatusInterface::CHARGEBACK              => 'RejectedCapture',
        StatusInterface::REJECTED                => 'Rejected',
        StatusInterface::REJECTED_CAPTURE        => 'RejectedCapture',
        StatusInterface::CANCELLED               => 'Cancelled',
    ];

    /**
     * @var array
     */
    private static $refundStatusToClassPathMap = [
        RefundHandlerInterface::REFUND_CREATED          => 'Refund\NullStatus',
        RefundHandlerInterface::REFUND_PENDING_APPROVAL => 'Refund\PendingApproval',
        RefundHandlerInterface::REFUND_REJECTED         => 'Refund\NullStatus',
        RefundHandlerInterface::REFUND_REFUND_REQUESTED => 'Refund\RefundRequested',
        RefundHandlerInterface::REFUND_CAPTURED         => 'Refund\Refunded',
        RefundHandlerInterface::REFUND_REFUNDED         => 'Refund\Refunded',
        RefundHandlerInterface::REFUND_CANCELLED        => 'Refund\Cancelled',
    ];

    /**
     * Create status object
     *
     * @param AbstractOrderStatus $ingenicoOrderStatus
     * @return StatusInterface|RefundHandlerInterface
     */
    public function create(AbstractOrderStatus $ingenicoOrderStatus)
    {
        $classPath = $this->resolveClassPath($ingenicoOrderStatus);

        return $this->objectManager->create(
            "Ingenico\Connect\Model\Ingenico\Status\\$classPath",
            [
                'gcOrderStatus' => $ingenicoOrderStatus,
            ]
        );
    }

    /**
     * Resolve class path
     *
     * @param AbstractOrderStatus $ingenicoOrderStatus
     * @return string|null
     */
    private function resolveClassPath(AbstractOrderStatus $ingenicoOrderStatus)
    {
        $classPath = null;
        if ($ingenicoOrderStatus instanceof Payment || $ingenicoOrderStatus instanceof CaptureResponse) {
            $classPath = isset(self::$paymentStatusToClassPathMap[$ingenicoOrderStatus->status])
                ? self::$paymentStatusToClassPathMap[$ingenicoOrderStatus->status]
                : null;
        } elseif ($ingenicoOrderStatus instanceof RefundResult) {
            $classPath = isset(self::$refundStatusToClassPathMap[$ingenicoOrderStatus->status])
                ? self::$refundStatusToClassPathMap[$ingenicoOrderStatus->status]
                : null;
        }
        if (null === $classPath) {
            throw new \RuntimeException('Given status is unknown.');
        }

        return $classPath;
    }
}
