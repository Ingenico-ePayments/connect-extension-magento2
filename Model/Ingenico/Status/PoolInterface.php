<?php

namespace Ingenico\Connect\Model\Ingenico\Status;

use Magento\Framework\Exception\NotFoundException;

/**
 * Class Pool
 * @package Ingenico\Connect\Model
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
