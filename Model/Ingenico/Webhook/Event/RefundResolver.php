<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Webhook\Event;

use Ingenico\Connect\Model\Ingenico\MerchantReference;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RefundOutput;
use Ingenico\Connect\Sdk\Domain\Refund\RefundResponse;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use RuntimeException;

class RefundResolver implements ResolverInterface
{
    /**
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * RefundResolver constructor.
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
            $event->refund->refundOutput->references->merchantReference
        );
    }

    /**
     * @param WebhooksEvent $event
     */
    private function assertCorrectEvent(WebhooksEvent $event)
    {
        if (!$event
            || !$event->refund
            || !$event->refund instanceof RefundResponse
            || !$event->refund->refundOutput
            || !$event->refund->refundOutput instanceof RefundOutput
        ) {
            throw new RuntimeException('Event does not match resolver.');
        }
    }
}
