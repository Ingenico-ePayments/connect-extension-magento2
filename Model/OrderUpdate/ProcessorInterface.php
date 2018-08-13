<?php

namespace Netresearch\Epayments\Model\OrderUpdate;

interface ProcessorInterface
{
    /**
     * Update Order Statuses
     *
     * @param string $scopeId
     */
    public function process($scopeId);
}
