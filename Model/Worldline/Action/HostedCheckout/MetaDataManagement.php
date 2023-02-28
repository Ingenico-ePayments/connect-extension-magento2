<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action\HostedCheckout;

use Ingenico\Connect\Sdk\Domain\Hostedcheckout\GetHostedCheckoutResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Model\Config;

class MetaDataManagement
{
    public const METHOD_SPECIFIC_OUTPUT_PROPERTIES = [
        'bankTransferPaymentMethodSpecificOutput',
        'cardPaymentMethodSpecificOutput',
        'cashPaymentMethodSpecificOutput',
        'directDebitPaymentMethodSpecificOutput',
        'eInvoicePaymentMethodSpecificOutput',
        'invoicePaymentMethodSpecificOutput',
        'mobilePaymentMethodSpecificOutput',
        'redirectPaymentMethodSpecificOutput',
        'sepaDirectDebitPaymentMethodSpecificOutput',
    ];

    /**
     * @param OrderInterface $order
     * @param GetHostedCheckoutResponse $statusResponse
     * @return int
     * @throws LocalizedException
     */
    public function getPaymentProductId(OrderInterface $order, GetHostedCheckoutResponse $statusResponse): int
    {
        if ($paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY)) {
            return (int) $paymentProductId;
        }

        $paymentOutput = $statusResponse->createdPaymentOutput->payment->paymentOutput;

        foreach (self::METHOD_SPECIFIC_OUTPUT_PROPERTIES as $propertyName) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            if (property_exists($paymentOutput, $propertyName) && $paymentOutput->$propertyName !== null) {
                return (int) $paymentOutput->$propertyName->paymentProductId;
            }
        }

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        throw new LocalizedException(__('No Payment Product ID found in RPP response'));
    }
}
