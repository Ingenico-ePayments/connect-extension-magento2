<?php

namespace Ingenico\Connect\Model\OrderUpdate;

interface ProcessorInterface
{
    /**
     * Update Order Statuses
     *
     * @param string $scopeId
     */
    public function process($scopeId);
}
