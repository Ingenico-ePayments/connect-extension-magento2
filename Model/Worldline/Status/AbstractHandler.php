<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Status;

use LogicException;
use Magento\Framework\Event\ManagerInterface;
use Worldline\Connect\Model\ConfigInterface;

abstract class AbstractHandler
{
    public const KEY_INGENICO_STATUS = 'worldline_status';

    protected const EVENT_CATEGORY = '';
    protected const EVENT_STATUS = '';

    /**
     * @var ManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $eventManager;

    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $config;

    public function __construct(ManagerInterface $eventManager, ConfigInterface $config)
    {
        $this->eventManager = $eventManager;
        $this->config = $config;
    }

    /**
     * @param array $data
     */
    final protected function dispatchMagentoEvent(array $data)
    {
        if ($this::EVENT_CATEGORY === '' || $this::EVENT_STATUS === '') {
            throw new LogicException('Event category or status is not set');
        }

        $this->eventManager->dispatch(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            implode(
                '_',
                [
                    'worldline',
                    'connect',
                    $this::EVENT_CATEGORY,
                    $this::EVENT_STATUS,
                ]
            ),
            $data
        );
    }
}
