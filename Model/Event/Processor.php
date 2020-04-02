<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Event;

use Ingenico\Connect\Model\Order\OrderServiceInterface;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEventFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Ingenico\Connect\Api\Data\EventInterface;
use Ingenico\Connect\Api\EventRepositoryInterface;
use Ingenico\Connect\Model\Ingenico\Status\ResolverInterface;
use Psr\Log\LoggerInterface;

class Processor
{
    const MESSAGE_NO_ORDER_FOUND = 'webhook: no order found';

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
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var OrderStatusHistoryInterfaceFactory
     */
    private $orderStatusHistoryFactory;

    /**
     * @var OrderServiceInterface
     */
    private $orderService;

    public function __construct(
        WebhooksEventFactory $webhookEventFactory,
        EventRepositoryInterface $eventRepository,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResolverInterface $statusResolver,
        SortOrderBuilder $sortOrderBuilder,
        LoggerInterface $logger,
        OrderManagementInterface $orderManagement,
        OrderStatusHistoryInterfaceFactory $orderStatusHistoryFactory,
        OrderServiceInterface $orderService
    ) {
        $this->webhookEventFactory = $webhookEventFactory;
        $this->eventRepository = $eventRepository;
        $this->orderRepository = $orderRepository;
        $this->statusResolver = $statusResolver;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->orderManagement = $orderManagement;
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
        $this->orderService = $orderService;
    }

    /**
     * @param int $limit
     * @throws LocalizedException
     */
    public function processBatch($limit = 20)
    {
        $this->searchCriteriaBuilder->addFilter(
            EventInterface::STATUS,
            [
                EventInterface::STATUS_NEW,
            ]
        );
        $this->searchCriteriaBuilder->setPageSize($limit);
        $this->searchCriteriaBuilder->addSortOrder(
            $this->sortOrderBuilder
                ->setField(EventInterface::CREATED_TIMESTAMP)
                ->setAscendingDirection()
                ->create()
        );

        $events = $this->eventRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();

        /** @var EventInterface $event */
        foreach ($events as $event) {
            $this->processEvent($event);
        }
    }

    /**
     * @param EventInterface $event
     * @throws LocalizedException
     */
    private function processEvent(EventInterface $event)
    {
        try {
            $order = $this->orderService->getByIncrementId($event->getOrderIncrementId());
        } catch (NoSuchEntityException $exception) {
            $this->logger->warning(
                self::MESSAGE_NO_ORDER_FOUND,
                [
                    'increment_id' => $event->getOrderIncrementId(),
                    'event_id' => $event->getEventId(),
                ]
            );
            $event->setStatus(EventInterface::STATUS_FAILED);
            $this->eventRepository->save($event);
            return;
        }

        /** @var WebhooksEvent $webhookEvent */
        $webhookEvent = $this->webhookEventFactory->create();
        $webhookEvent = $webhookEvent->fromJson($event->getPayload());

        try {
            $this->statusResolver->resolve($order, $this->extractStatusObject($webhookEvent));
            $order->setDataChanges(true);
            $this->orderRepository->save($order);
            $event->setStatus(EventInterface::STATUS_SUCCESS);
            $this->eventRepository->save($event);
        } catch (\Exception $exception) {
            $event->setStatus(EventInterface::STATUS_FAILED);
            $this->eventRepository->save($event);
            $this->orderManagement->addComment(
                $order->getEntityId(),
                $this->orderStatusHistoryFactory->create([
                    'data' => [
                        'comment' => __(
                            'Error occurred while trying to process the webhook: %1',
                            $exception->getMessage()
                        )->render(),
                        'status' => $order->getStatus(),
                    ],
                ])
            );
        }
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
