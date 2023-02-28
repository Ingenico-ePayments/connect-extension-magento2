<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class AdditionalDataAssignObserver extends AbstractDataAssignObserver
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
    }
}
