<?php

namespace Netresearch\Epayments\Model\Ingenico\Webhooks;

class PaymentEventDataResolver implements EventDataResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function getResponse(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event)
    {
        $this->assertCorrectEvent($event);

        return $event->payment;
    }

    /**
     * {@inheritdoc}
     */
    public function getMerchantOrderReference(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event)
    {
        $this->assertCorrectEvent($event);
        $merchantOrderId = (int)$event->payment->paymentOutput->references->merchantReference;

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
            || !$event->payment
            || !$event->payment instanceof \Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse
            || !$event->payment->paymentOutput
            || !$event->payment->paymentOutput instanceof \Ingenico\Connect\Sdk\Domain\Payment\Definitions\PaymentOutput
        ) {
            throw new \RuntimeException('Event does not match resolver.');
        }
    }
}
