<?php

namespace Ingenico\Connect\Model\OrderUpdate;

use Magento\Framework\Logger\Handler\Base;

class Logger extends Base
{
    /** @var string */
    protected $fileName = '/var/log/order_update.log';

    /** @var int */
    protected $loggerType = \Monolog\Logger::INFO;
}
