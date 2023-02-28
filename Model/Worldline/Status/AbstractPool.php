<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManager\TMapFactory;

/**
 * Class AbstractPool
 */
abstract class AbstractPool
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
    private $statusHandlers = [];

    /**
     * @var TMapFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        if (!isset($this->statusHandlers[$statusCode])) {
            throw new NotFoundException(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
