<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Event;

use Ingenico\Connect\Api\Data\EventInterface;
use Ingenico\Connect\Api\EventManagerInterface;
use Ingenico\Connect\Api\EventRepositoryInterface;
use Ingenico\Connect\Model\Event\Processor\SecondAttempts;
use Magento\Framework\Api\SearchCriteriaBuilder;

class EventManager implements EventManagerInterface
{
    /**
     * @var EventRepositoryInterface
     */
    private $eventRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * EventManager constructor.
     *
     * @param EventRepositoryInterface $eventRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        EventRepositoryInterface $eventRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->eventRepository = $eventRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getHostedCheckoutEvents(
        int $status = EventInterface::STATUS_NEW
    ) {
        return $this->eventRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter(EventInterface::STATUS, $status)
                ->addFilter(
                    EventInterface::PAYLOAD,
                    '%%"hostedCheckoutId":"%%',
                    'like'
                )
                ->create()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function findHostedCheckoutIdsInEvents(int $eventStatus = EventInterface::STATUS_NEW): array
    {
        $hostedCheckoutIds = [];

        foreach ($this->getHostedCheckoutEvents($eventStatus)->getItems() as $event) {
            if ($json = SecondAttempts::getValidatedPayload($event)) {
                $hostedCheckoutId = $json['payment']['hostedCheckoutSpecificOutput']['hostedCheckoutId'];
                if (!in_array($hostedCheckoutId, $hostedCheckoutIds)) {
                    $hostedCheckoutIds[] = $hostedCheckoutId;
                }
            }
        }

        return $hostedCheckoutIds;
    }

    /**
     * {@inheritDoc}
     */
    public function getEventsByHostedCheckoutId(
        string $hostedCheckoutId,
        int $status = EventInterface::STATUS_NEW
    ) {
        return $this->eventRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter(EventInterface::STATUS, $status)
                ->addFilter(
                    EventInterface::PAYLOAD,
                    sprintf('%%"hostedCheckoutId":"%1$s"%%', $hostedCheckoutId),
                    'like'
                )
                ->create()
        );
    }
}
