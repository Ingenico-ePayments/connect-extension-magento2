<?php

namespace Ingenico\Connect\Model\Ingenico\Webhook\Event;

use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use InvalidArgumentException;

interface ResolverInterface
{
    /**
     * @param WebhooksEvent $event
     * @return string
     * @throws InvalidArgumentException if event reference does not originate from this system
     */
    public function getMerchantOrderReference(WebhooksEvent $event): string;
}
