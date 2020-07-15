<?php

declare(strict_types=1);

namespace Ingenico\Connect\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class DebugHandler extends Base
{
    /** @var int */
    protected $loggerType = Logger::DEBUG;
}
