<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Event;

use Exception;
use Ingenico\Connect\Model\Ingenico\Status\Payment\ResolverInterface as PaymentResolverInterface;
use Ingenico\Connect\Model\Ingenico\Status\Refund\ResolverInterface as RefundResolverInterface;
use Ingenico\Connect\Model\Order\OrderServiceInterface;
use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\Domain\Refund\RefundResponse;
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
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use RuntimeException;

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

    /**
     * @var PaymentResolverInterface
     */
    private $paymentResolver;

    /**
     * @var RefundResolverInterface
     */
    private $refundResolver;

    public function __construct(
        WebhooksEventFactory $webhookEventFactory,
        EventRepositoryInterface $eventRepository,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        LoggerInterface $logger,
        OrderManagementInterface $orderManagement,
        OrderStatusHistoryInterfaceFactory $orderStatusHistoryFactory,
        OrderServiceInterface $orderService,
        PaymentResolverInterface $paymentResolver,
        RefundResolverInterface $refundResolver
    ) {
        $this->webhookEventFactory = $webhookEventFactory;
        $this->eventRepository = $eventRepository;
        $this->orderRepository = $orderRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->orderManagement = $orderManagement;
        $this->orderStatusHistoryFactory = $orderStatusHistoryFactory;
        $this->orderService = $orderService;
        $this->paymentResolver = $paymentResolver;
        $this->refundResolver = $refundResolver;
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
            $statusResponseObject = $this->extractStatusObject($webhookEvent);
            $className = get_class($statusResponseObject);
            switch ($className) {
                case PaymentResponse::class:
                    $this->paymentResolver->resolve($order, $statusResponseObject);
                    break;
                case RefundResponse::class:
                    if ($order instanceof Order) {
                        // @todo: extract the fetching of the correct credit memo
                        // according to the status response to a separate class:
                        // This is so wrong... Don't release like this!
                        // It will have unforeseen side-effects with multiple refunds on one order
                        // SCGC-409 will fix this.
                        $creditMemo = $order->getCreditmemosCollection()->getFirstItem();
                        $this->refundResolver->resolve($creditMemo, $statusResponseObject);
                    }
                    break;
                default:
                    throw new LocalizedException(__('Unsupported status object: %1', get_class($statusResponseObject)));
            }

            $order->setDataChanges(true);
            $this->orderRepository->save($order);
            $event->setStatus(EventInterface::STATUS_SUCCESS);
            $this->eventRepository->save($event);
        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
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
     * @return PaymentResponse|RefundResponse
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
                throw new RuntimeException("Event type {$event->type} not supported.");
        }
    }
}
