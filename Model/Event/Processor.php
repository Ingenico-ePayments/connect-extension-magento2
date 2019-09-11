<?php
/**
 * See LICENSE.md for license details.
 */

namespace Ingenico\Connect\Model\Event;

use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEventFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Api\Data\EventInterface;
use Ingenico\Connect\Api\EventRepositoryInterface;
use Ingenico\Connect\Model\Ingenico\Status\ResolverInterface;

class Processor
{
    /**
     * @var WebhooksEventFactory
     */
    private $webhookEventFactory;

    /**
     * @var EventRepositoryInterface
     */
    private $eventRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaBuilderFactory;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * Processor constructor.
     *
     * @param WebhooksEventFactory $webhookEventFactory
     * @param EventRepositoryInterface $eventRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilderFactory $criteriaBuilderFactory
     * @param ResolverInterface $statusResolver
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        WebhooksEventFactory $webhookEventFactory,
        EventRepositoryInterface $eventRepository,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilderFactory $criteriaBuilderFactory,
        ResolverInterface $statusResolver,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->webhookEventFactory = $webhookEventFactory;
        $this->eventRepository = $eventRepository;
        $this->orderRepository = $orderRepository;
        $this->criteriaBuilderFactory = $criteriaBuilderFactory;
        $this->statusResolver = $statusResolver;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * @param int $limit
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processBatch($limit = 20)
    {
        $criteriaBuilder = $this->criteriaBuilderFactory->create();
        $criteriaBuilder->addFilter(
            EventInterface::STATUS,
            [
                EventInterface::STATUS_NEW,
                EventInterface::STATUS_FAILED,
            ],
            'in'
        );
        $criteriaBuilder->setPageSize($limit);
        $criteriaBuilder->addSortOrder(
            $this->sortOrderBuilder->setField(EventInterface::CREATED_TIMESTAMP)
                                   ->setAscendingDirection()
                                   ->create()
        );
        $events = $this->eventRepository->getList($criteriaBuilder->create())->getItems();

        $orderIncrementIds = array_reduce(
            $events,
            /**
             * @param $carry string[]
             * @param $event EventInterface
             * @return string[]
             */
            function ($carry, $event) {
                $carry[] = $event->getOrderIncrementId();

                return $carry;
            },
            []
        );
        $criteriaBuilder->addFilter('increment_id', $orderIncrementIds, 'in');

        $orders = $this->orderRepository->getList($criteriaBuilder->create())->getItems();

        /** @var EventInterface $event */
        foreach ($events as $event) {
            $this->processEvent($event, $orders);
        }
    }

    /**
     * @param EventInterface $event
     * @param Order[] $orders
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processEvent(EventInterface $event, array $orders)
    {
        /** @var WebhooksEvent $webhookEvent */
        $webhookEvent = $this->webhookEventFactory->create();
        $webhookEvent = $webhookEvent->fromJson($event->getPayload());
        $order = $this->getOrderForEvent($orders, $event);
        $event->setStatus(EventInterface::STATUS_PROCESSING);
        $this->eventRepository->save($event);
        try {
            $this->statusResolver->resolve($order, $this->extractStatusObject($webhookEvent));
            $order->setDataChanges(true);
            $this->orderRepository->save($order);
            $event->setStatus(EventInterface::STATUS_SUCCESS);
            $this->eventRepository->save($event);
        } catch (\Exception $exception) {
            $event->setStatus(EventInterface::STATUS_FAILED);
            $this->eventRepository->save($event);
        }
    }

    /**
     * @param OrderInterface[] $orders
     * @param EventInterface $event
     * @return OrderInterface
     */
    private function getOrderForEvent($orders, EventInterface $event)
    {
        $result = array_filter(
            $orders,
            function ($order) use ($event) {
                /** @var OrderInterface $order */
                return $order->getIncrementId() === $event->getOrderIncrementId();
            }
        );

        return array_shift($result);
    }

    /**
     * @param WebhooksEvent $event
     * @return \Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse|\Ingenico\Connect\Sdk\Domain\Refund\RefundResponse
     */
    private function extractStatusObject(WebhooksEvent $event)
    {
        $objectType = explode('.', $event->type)[0];
        switch ($objectType) {
            case 'payment':
                return $event->payment;
            case 'refund':
                return $event->refund;
            case 'payout':
            default:
                throw new \RuntimeException("Event type {$event->type} not supported.");
        }
    }
}
