<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Event;

use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEventFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use Worldline\Connect\Api\Data\EventInterface;
use Worldline\Connect\Api\EventRepositoryInterface;
use Worldline\Connect\Model\Order\OrderServiceInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface as PaymentResolverInterface;
use Worldline\Connect\Model\Worldline\Status\Refund\ResolverInterface as RefundResolverInterface;

use function sprintf;
use function str_starts_with;

class Processor
{
    public const MESSAGE_NO_ORDER_FOUND = 'webhook: no order found';

    /**
     * @var WebhooksEventFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $webhookEventFactory;

    /**
     * @var EventRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $eventRepository;

    /**
     * @var OrderRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /**
     * @var SortOrderBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $sortOrderBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    /**
     * @var OrderServiceInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderService;

    /**
     * @var PaymentResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentResolver;

    /**
     * @var RefundResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $refundResolver;

    public function __construct(
        WebhooksEventFactory $webhookEventFactory,
        EventRepositoryInterface $eventRepository,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        LoggerInterface $logger,
        OrderServiceInterface $orderService,
        PaymentResolverInterface $paymentResolver,
        RefundResolverInterface $refundResolver,
    ) {
        $this->webhookEventFactory = $webhookEventFactory;
        $this->eventRepository = $eventRepository;
        $this->orderRepository = $orderRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
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
        $this->processEvents($this->getEvents($limit));
    }

    /**
     * @param int $limit
     * @return array<EventInterface>
     */
    private function getEvents(int $limit): array
    {
        $this->searchCriteriaBuilder->addFilter(EventInterface::STATUS, [
            EventInterface::STATUS_NEW,
        ]);
        $this->searchCriteriaBuilder->setPageSize($limit);
        $this->searchCriteriaBuilder->addSortOrder(
            $this->sortOrderBuilder
                ->setField(EventInterface::CREATED_TIMESTAMP)
                ->setAscendingDirection()
                ->create()
        );

        return $this->eventRepository
            ->getList($this->searchCriteriaBuilder->create())
            ->getItems();
    }

    /**
     * @param array<EventInterface> $events
     * @throws CouldNotSaveException
     */
    private function processEvents(array $events): void
    {
        foreach ($events as $event) {
            try {
                $this->processEvent($event);
                $event->setStatus(EventInterface::STATUS_SUCCESS);
                $this->eventRepository->save($event);
                $this->logger->info('Processed event', [
                    'event_id' => $event->getId(),
                ]);
            } catch (Throwable $exception) {
                $this->logger->warning('Could not process event', [
                    'event_id' => $event->getId(),
                    'exception' => $exception->getMessage(),
                ]);
                $event->setStatus(EventInterface::STATUS_FAILED);
                $this->eventRepository->save($event);
            }
        }
    }

    /**
     * @param EventInterface $event
     * @throws LocalizedException
     */
    private function processEvent(EventInterface $event): void
    {
        /** @var WebhooksEvent $webhookEvent */
        $webhookEvent = $this->webhookEventFactory->create()->fromJson($event->getPayload());
        if ($this->checkEndpointTest($webhookEvent)) {
            return;
        }

        if ($webhookEvent->payment !== null) {
            $order = $this->handlePaymentEvent($webhookEvent);
        } elseif ($webhookEvent->refund !== null) {
            $order = $this->handleRefundEvent($webhookEvent);
        } else {
            throw new RuntimeException(sprintf('Event type %s not supported.', $webhookEvent->type));
        }

        $order->addCommentToStatusHistory($event->getPayload());
        $order->setDataChanges(true);

        $this->orderRepository->save($order);
    }

    private function checkEndpointTest(WebhooksEvent $event): bool
    {
        return str_starts_with((string) $event->id, 'TEST');
    }

    private function handlePaymentEvent(WebhooksEvent $webhookEvent): Order
    {
        $payment = $webhookEvent->payment;

        /** @var Order $order */
        $order = $this->orderService->getByIncrementId($payment->paymentOutput->references->merchantReference);

        try {
            $this->paymentResolver->resolve($order, $payment);
            // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (Throwable) {
        }

        return $order;
    }

    private function handleRefundEvent(WebhooksEvent $webhookEvent): Order
    {
        $refund = $webhookEvent->refund;

        /** @var Order $order */
        $order = $this->orderService->getByIncrementId($refund->refundOutput->references->merchantReference);

        try {
            $this->refundResolver->resolve($order, $refund);
            // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (Throwable) {
        }

        return $order;
    }
}
