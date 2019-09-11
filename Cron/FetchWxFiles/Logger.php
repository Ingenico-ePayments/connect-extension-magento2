<?php

namespace Ingenico\Connect\Cron\FetchWxFiles;

use Magento\Framework\Logger\Handler\Base;

class Logger extends Base
{
    /** @var string */
    protected $fileName = '/var/log/wx_update_log.log';

    /** @var int */
    protected $loggerType = \Monolog\Logger::INFO;
}
