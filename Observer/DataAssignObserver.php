<?php

namespace Ingenico\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Ingenico\Connect\Model\Config;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $paymentInfo = $this->readPaymentModelArgument($observer);
        foreach ($additionalData as $key => $value) {
            $paymentInfo->setAdditionalInformation($key, $value);
        }
        if (!is_array($additionalData) || !isset($additionalData[Config::PRODUCT_ID_KEY])) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($additionalData as $key => $value) {
            if (is_object($value)) {
                // do not try to store objects into additional information
                continue;
            }
            $paymentInfo->setAdditionalInformation(
                $key,
                $value
            );
        }
    }
}
