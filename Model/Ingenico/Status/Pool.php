<?php

namespace Netresearch\Epayments\Model\Ingenico\Status;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManager\TMap;
use Magento\Framework\ObjectManager\TMapFactory;

/**
 * Class Pool
 * @package Netresearch\Epayments\Model
 */
class Pool implements PoolInterface
{
    /** @var HandlerInterface[]|TMap */
    private $statusHandlers;

    /**
     * Pool constructor.
     * @param TMapFactory $tMapFactory
     * @param array $statusHandlers
     */
    public function __construct(TMapFactory $tMapFactory, array $statusHandlers = [])
    {
        $this->statusHandlers = $tMapFactory->createSharedObjectsMap(
            [
                'array' => $statusHandlers,
                'type' => HandlerInterface::class,
            ]
        );
    }

    /**
     * Query status pool for specific status handler
     *
     * @param $statusCode
     * @return HandlerInterface
     * @throws NotFoundException
     */
    public function get($statusCode)
    {
        if (!isset($this->statusHandlers[$statusCode])) {
            throw new NotFoundException(
                __('Handler for status %1 is not defined.', $statusCode)
            );
        }

        return $this->statusHandlers[$statusCode];
    }
}
