<?php

namespace Netresearch\Epayments\Model;

use Magento\Framework\Logger\Handler\Base;

class RequestLogger extends Base
{
    /** @var string */
    protected $fileName = '/var/log/ingenico_epayments.log';

    /** @var int */
    protected $loggerType = \Monolog\Logger::DEBUG;
}
