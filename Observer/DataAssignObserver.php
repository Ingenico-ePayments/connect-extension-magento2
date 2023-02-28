<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Worldline\Connect\Model\Config;

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
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (!is_array($additionalData) || !isset($additionalData[Config::PRODUCT_ID_KEY])) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($additionalData as $key => $value) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
