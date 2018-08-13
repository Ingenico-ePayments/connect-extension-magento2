<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Magento\Framework\Exception\NotFoundException;

/**
 * Class Pool
 * @package Netresearch\Epayments\Model
 */
interface PoolInterface
{
    /**
     * Query status pool for specific status handler
     *
     * @param $statusCode
     * @return HandlerInterface
     * @throws NotFoundException
     */
    public function get($statusCode);
}
