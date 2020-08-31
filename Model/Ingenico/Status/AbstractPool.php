<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManager\TMapFactory;

/**
 * Class AbstractPool
 */
abstract class AbstractPool
{
    private $statusHandlers = [];

    /**
     * @var TMapFactory
     */
    private $tMapFactory;

    /**
     * Pool constructor.
     *
     * @param TMapFactory $tMapFactory
     */
    public function __construct(TMapFactory $tMapFactory)
    {
        $this->tMapFactory = $tMapFactory;
    }

    /**
     * @param string $statusCode
     * @return mixed
     * @throws NotFoundException
     */
    protected function getHandler(string $statusCode)
    {
        if (!isset($this->statusHandlers[$statusCode])) {
            throw new NotFoundException(
                __('Handler for status %1 is not defined.', $statusCode)
            );
        }

        return $this->statusHandlers[$statusCode];
    }

    /**
     * @param string $type
     * @param array $statusHandlers
     */
    protected function createSharedObjectsMap(
        string $type,
        array $statusHandlers
    ) {
        $this->statusHandlers = $this->tMapFactory->createSharedObjectsMap(
            [
                'array' => $statusHandlers,
                'type' => $type,
            ]
        );
    }
}
