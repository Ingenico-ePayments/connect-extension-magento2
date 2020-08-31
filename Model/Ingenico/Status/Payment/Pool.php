<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status\Payment;

use Ingenico\Connect\Model\Ingenico\Status\AbstractPool;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManager\TMapFactory;

/**
 * Class Pool
 *
 * @package Ingenico\Connect\Model
 */
class Pool extends AbstractPool implements PoolInterface
{
    /**
     * Pool constructor.
     *
     * @param TMapFactory $tMapFactory
     * @param array $statusHandlers
     */
    public function __construct(TMapFactory $tMapFactory, array $statusHandlers = [])
    {
        parent::__construct($tMapFactory);
        $this->createSharedObjectsMap(HandlerInterface::class, $statusHandlers);
    }

    /**
     * @param string $statusCode
     * @return HandlerInterface
     * @throws NotFoundException
     */
    public function get(string $statusCode): HandlerInterface
    {
        return $this->getHandler($statusCode);
    }
}
