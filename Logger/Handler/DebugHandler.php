<?php

declare(strict_types=1);

namespace Worldline\Connect\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class DebugHandler extends Base
{
    /** @var int */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $loggerType = Logger::DEBUG;
}
