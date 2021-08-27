<?php

declare(strict_types=1);

namespace Ingenico\Connect\Controller\Webhooks;

/**
 * Class Payment
 *
 * @package Ingenico\Connect\Controller\Webhooks
 * @deprecated Only the core webhook endpoint is needed
 */
class Payment extends Deprecated
{
    public function execute()
    {
        $this->addDeprecationNotice();
        return parent::execute();
    }
}
