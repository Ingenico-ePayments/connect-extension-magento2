<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Status;

use LogicException;
use Magento\Framework\Event\ManagerInterface;

abstract class AbstractHandler
{
    public const KEY_INGENICO_STATUS = 'ingenico_status';

    protected const EVENT_CATEGORY = '';
    protected const EVENT_STATUS = '';

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    public function __construct(ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
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
            implode(
                '_',
                [
                    'ingenico',
                    'connect',
                    $this::EVENT_CATEGORY,
                    $this::EVENT_STATUS,
                ]
            ),
            $data
        );
    }
}
