<?php

declare(strict_types=1);

namespace Worldline\Connect\Controller\Webhooks;

/**
 * Class Payment
 *
 * @package Worldline\Connect\Controller\Webhooks
 * @deprecated Only the core webhook endpoint is needed
 */
class Payment extends Deprecated
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function execute()
    {
        $this->addDeprecationNotice();
        return parent::execute();
    }
}
