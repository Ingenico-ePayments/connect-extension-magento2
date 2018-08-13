<?php

namespace Netresearch\Epayments\Model\Ingenico\Webhooks;

interface EventDataResolverInterface
{
    /**
     * @param \Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event
     * @return \Ingenico\Connect\Sdk\Domain\Definitions\AbstractOrderStatus
     * @throws \RuntimeException if event does not match certain resolver
     */
    public function getResponse(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event);

    /**
     * @param \Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event
     * @return int
     * @throws \RuntimeException if event does not match certain resolver or merchant order id is missing
     */
    public function getMerchantOrderReference(\Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event);
}
