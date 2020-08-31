<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status;

use Ingenico\Connect\Model\StatusResponseManagerInterface;
use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;

abstract class AbstractResolver
{
    protected const KEY_STATUS = 'status';
    protected const KEY_STATUS_CODE_CHANGE_DATE_TIME = 'status_code_change_date_time';

    /**
     * @var StatusResponseManagerInterface
     */
    private $statusResponseManager;

    public function __construct(StatusResponseManagerInterface $statusResponseManager)
    {
        $this->statusResponseManager = $statusResponseManager;
    }

    protected function isStatusNewerThanPreviousStatus(OrderInterface $order, AbstractOrderStatus $status)
    {
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        $newStatusChangeDateTime = $status->statusOutput->statusCodeChangeDateTime;

        if (array_key_exists(static::KEY_STATUS_CODE_CHANGE_DATE_TIME, $additionalInformation) &&
            $additionalInformation[static::KEY_STATUS_CODE_CHANGE_DATE_TIME] >= $newStatusChangeDateTime
        ) {
            return false;
        }

        // This is for legacy orders, can be removed when the Ingenico object is no longer stored in the payment:
        $existingStatus = $this->statusResponseManager->get($order->getPayment(), $status->id);

        if ($existingStatus
            && $existingStatus->statusOutput->statusCodeChangeDateTime >= $newStatusChangeDateTime) {
            return false;
        }
        // --- end

        return true;
    }

    protected function updateStatusCodeChangeDate(OrderInterface $order, AbstractOrderStatus $status)
    {
        $this->updateAdditionalInformation(
            $order,
            static::KEY_STATUS_CODE_CHANGE_DATE_TIME,
            $status->statusOutput->statusCodeChangeDateTime
        );
    }

    protected function updateStatus(OrderInterface $order, AbstractOrderStatus $status)
    {
        $this->updateAdditionalInformation($order, static::KEY_STATUS, $status->status);
    }

    private function updateAdditionalInformation(OrderInterface $order, string $key, string $value)
    {
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        $additionalInformation[$key] = $value;
        $order->getPayment()->setAdditionalInformation($additionalInformation);
    }

    /**
     * @param InfoInterface $payment
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws LocalizedException
     * @deprecated This method is here for legacy reasons.
     */
    protected function preparePayment(InfoInterface $payment, AbstractOrderStatus $ingenicoStatus)
    {
        $payment->setTransactionId($ingenicoStatus->id);

        if (!$this->statusResponseManager->get($payment, $ingenicoStatus->id)) {
            $this->updatePayment($payment, $ingenicoStatus);
        }
    }

    /**
     * @param InfoInterface $payment
     * @param AbstractOrderStatus $ingenicoStatus
     * @throws LocalizedException
     * @deprecated This method is here for legacy reasons.
     */
    protected function updatePayment(InfoInterface $payment, AbstractOrderStatus $ingenicoStatus)
    {
        $this->statusResponseManager->set(
            $payment,
            $ingenicoStatus->id,
            $ingenicoStatus
        );
    }
}
