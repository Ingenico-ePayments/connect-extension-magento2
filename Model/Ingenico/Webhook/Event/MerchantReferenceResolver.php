<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Webhook\Event;

use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\NoSuchEntityException;

class MerchantReferenceResolver
{
    /**
     * @var ConfigInterface
     */
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

        throw new NoSuchEntityException(__('No merchant reference found'));
    }

    /**
     * @param string $merchantReference
     * @return string
     * @throws InvalidArgumentException
     */
    public function stripReferencePrefix(string $merchantReference): string
    {
        if ($this->config->getReferencePrefix() !== ''
            && strpos($merchantReference, $this->config->getReferencePrefix()) !== 0) {
            // if there is a nonempty prefix set and it could not be found in the reference
            throw new InvalidArgumentException(
                __('This reference is most likely not originating from this system.')
            );
        }

        return str_replace($this->config->getReferencePrefix(), '', $merchantReference);
    }

    private function hasProperties(WebhooksEvent $event, string $properties): bool
    {
        $parts = explode('.', $properties);
        $obj = clone $event;

        while (count($parts) > 1) {
            if (!property_exists($obj, $parts[0]) || !$obj->{$parts[0]}) {
                return false;
            }

            $obj = $obj->{$parts[0]};
            array_shift($parts);
        }

        return true;
    }
}
