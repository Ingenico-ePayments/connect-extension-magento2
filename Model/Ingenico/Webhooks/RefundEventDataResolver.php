<?php

namespace Ingenico\Connect\Model\Ingenico\Webhooks;

use Ingenico\Connect\Model\Ingenico\MerchantReference;

class RefundEventDataResolver implements EventDataResolverInterface
{
    /**
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * RefundEventDataResolver constructor.
     *
     * @param MerchantReference $merchantReference
     */
    public function __construct(MerchantReference $merchantReference)
    {
        $this->merchantReference = $merchantReference;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event)
    {
        $this->assertCorrectEvent($event);

        return $event->refund;
    }

    /**
     * @param \Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event
     */
    private function assertCorrectEvent(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event)
    {
        if (!$event
            || !$event->refund
            || !$event->refund instanceof \Ingenico\Connect\Sdk\Domain\Refund\RefundResponse
            || !$event->refund->refundOutput
            || !$event->refund->refundOutput instanceof \Ingenico\Connect\Sdk\Domain\Payment\Definitions\RefundOutput
        ) {
            throw new \RuntimeException('Event does not match resolver.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantOrderReference(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event)
    {
        $this->assertCorrectEvent($event);
        $merchantOrderId = $this->merchantReference->extractOrderReference(
            $event->refund->refundOutput->references->merchantReference
        );

        return $merchantOrderId;
    }
}
