<?php

namespace Netresearch\Epayments\Model\Ingenico\Webhooks;

use Netresearch\Epayments\Model\Ingenico\MerchantReference;

class PaymentEventDataResolver implements EventDataResolverInterface
{
    /**
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * PaymentEventDataResolver constructor.
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

        return $event->payment;
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

    /**
     * {@inheritdoc}
     */
    public function getMerchantOrderReference(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event)
    {
        $this->assertCorrectEvent($event);
        $merchantOrderId = $this->merchantReference->extractOrderReference(
            $event->payment->paymentOutput->references->merchantReference
        );

        return $merchantOrderId;
    }
}
