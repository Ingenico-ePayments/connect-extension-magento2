<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Webhook\Event;

use Ingenico\Connect\Model\Ingenico\MerchantReference;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PaymentOutput;
use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use RuntimeException;

class PaymentResolver implements ResolverInterface
{
    /**
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * PaymentResolver constructor.
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
    public function getMerchantOrderReference(WebhooksEvent $event): string
    {
        $this->assertCorrectEvent($event);
        return $this->merchantReference->extractOrderReference(
            $event->payment->paymentOutput->references->merchantReference
        );
    }

    /**
     * @param WebhooksEvent $event
     */
    private function assertCorrectEvent(WebhooksEvent $event)
    {
        if (!$event
            || !$event->payment
            || !$event->payment instanceof PaymentResponse
            || !$event->payment->paymentOutput
            || !$event->payment->paymentOutput instanceof PaymentOutput
        ) {
            throw new RuntimeException('Event does not match resolver.');
        }
    }
}
