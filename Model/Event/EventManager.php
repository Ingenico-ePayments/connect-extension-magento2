<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Event;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Worldline\Connect\Api\Data\EventInterface;
use Worldline\Connect\Api\EventManagerInterface;
use Worldline\Connect\Api\EventRepositoryInterface;
use Worldline\Connect\Model\Event\Processor\SecondAttempts;

class EventManager implements EventManagerInterface
{
    /**
     * @var EventRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $eventRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
                    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                    sprintf('%%"hostedCheckoutId":"%1$s"%%', $hostedCheckoutId),
                    'like'
                )
                ->create()
        );
    }
}
