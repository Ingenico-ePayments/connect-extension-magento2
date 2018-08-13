<?php

namespace Netresearch\Epayments\Model\Ingenico\Webhooks;

class RefundEventDataResolver implements EventDataResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function getResponse(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event)
    {
        $this->assertCorrectEvent($event);

        return $event->refund;
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantOrderReference(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event)
    {
        $this->assertCorrectEvent($event);
        $merchantOrderId = (int)$event->refund->refundOutput->references->merchantReference;

        if ($merchantOrderId <= 0) {
            throw new \RuntimeException('Merchant order id value is missing in Event response.');
        }

        return $merchantOrderId;
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
}
