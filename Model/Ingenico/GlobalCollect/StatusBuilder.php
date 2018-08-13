<?php

namespace Netresearch\Epayments\Model\Ingenico\GlobalCollect;

use Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus;
use Magento\Framework\Exception\IntegrationException;
use Netresearch\Epayments\Model\Ingenico\GlobalCollect\Wx\DataRecord;

class StatusBuilder
{
    /**
     * @var OrderStatusFactory
     */
    private $orderStatusFactory;

    /**
     * StatusBuilder constructor.
     *
     * @param OrderStatusFactory $orderStatusFactory
     */
    public function __construct(OrderStatusFactory $orderStatusFactory)
    {
        $this->orderStatusFactory = $orderStatusFactory;
    }

    /**
     * @param DataRecord $dataRecord
     * @return AbstractOrderStatus|false
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\IntegrationException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(DataRecord $dataRecord)
    {
        // some statuses are used for multiple use cases (refund vs capture), we therefore have to check some other things too
        $possibleStatuses = StatusMapper::getConnectStatus(
            $dataRecord->getPaymentData()->getPaymentStatus(),
            $dataRecord->getPaymentData()->getPaymentGroupId()
        );

        if (empty($possibleStatuses)) {
            // no applicable status found
            return false;
        } elseif (count($possibleStatuses) === 1) {
            $definiteStatus = array_shift($possibleStatuses);
            return $this->orderStatusFactory->create($definiteStatus, $dataRecord);
        } else {
            // multiple possible statuses - need to consult record type/category
            $message = 'Got multiple possible statuses, handling not implemented. Statuses: %statuses';
            throw new IntegrationException(
                __(
                    $message,
                    ['statuses' => implode(', ', $possibleStatuses)]
                )
            );
        }
    }
}
