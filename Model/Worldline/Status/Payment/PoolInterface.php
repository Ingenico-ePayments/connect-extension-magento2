<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status\Payment;

use Magento\Framework\Exception\NotFoundException;

/**
 * Class Pool
 */
// phpcs:ignore SlevomatCodingStandard.Classes.SuperfluousInterfaceNaming.SuperfluousSuffix
interface PoolInterface
{
    /**
     * @param string $statusCode
     * @return HandlerInterface
     * @throws NotFoundException
     */
    public function get(string $statusCode): HandlerInterface;
}
