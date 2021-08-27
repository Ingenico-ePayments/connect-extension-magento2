<?php

declare(strict_types=1);

namespace Ingenico\Connect\Controller\Webhooks;

/**
 * Class Refund
 *
 * @package Ingenico\Connect\Controller\Webhooks
 * @deprecated Only the core webhook endpoint is needed
 */
class Refund extends Deprecated
{
    public function execute()
    {
        $this->addDeprecationNotice();
        return parent::execute();
    }
}
