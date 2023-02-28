<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Webhook\Event;

use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Magento\Framework\Exception\NoSuchEntityException;
use Worldline\Connect\Model\ConfigInterface;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse

class MerchantReferenceResolver
{
    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param WebhooksEvent $event
     * @return string
     * @throws NoSuchEntityException
     */
    public function getMerchantReference(WebhooksEvent $event): string
    {
        if ($this->hasProperties($event, 'payment.paymentOutput.references.merchantReference')) {
            return (string) $event->payment->paymentOutput->references->merchantReference;
        }

        if ($this->hasProperties($event, 'refund.refundOutput.references.merchantReference')) {
            return (string) $event->refund->refundOutput->references->merchantReference;
        }

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        throw new NoSuchEntityException(__('No merchant reference found'));
    }

    private function hasProperties(WebhooksEvent $event, string $properties): bool
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $parts = explode('.', $properties);
        $obj = clone $event;

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        while (count($parts) > 1) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            if (!property_exists($obj, $parts[0]) || !$obj->{$parts[0]}) {
                return false;
            }

            $obj = $obj->{$parts[0]};
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            array_shift($parts);
        }

        return true;
    }
}
