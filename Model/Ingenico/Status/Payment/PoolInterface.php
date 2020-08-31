<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment;

use Magento\Framework\Exception\NotFoundException;

/**
 * Class Pool
 */
interface PoolInterface
{
    /**
     * @param string $statusCode
     * @return HandlerInterface
     * @throws NotFoundException
     */
    public function get(string $statusCode): HandlerInterface;
}
