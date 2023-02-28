<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Event;

use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\Domain\Refund\RefundResponse;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEventFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderStatusHistoryInterfaceFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use Worldline\Connect\Api\Data\EventInterface;
use Worldline\Connect\Api\EventRepositoryInterface;
use Worldline\Connect\Helper\Data;
use Worldline\Connect\Model\Order\OrderServiceInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface as PaymentResolverInterface;
use Worldline\Connect\Model\Worldline\Status\Refund\ResolverInterface as RefundResolverInterface;

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
     * @var OrderManagementInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderManagement;

    /**
     * @var OrderStatusHistoryInterfaceFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderStatusHistoryFactory;

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
    // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded, SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    private function processEvent(EventInterface $event)
    {
        try {
            $order = $this->orderService->getByIncrementId($event->getOrderIncrementId());
        } catch (Throwable $exception) {
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

        try {
            /** @var WebhooksEvent $webhookEvent */
            $webhookEvent = $this->webhookEventFactory->create();
            $webhookEvent = $webhookEvent->fromJson($event->getPayload());

            $statusResponseObject = $this->extractStatusObject($webhookEvent);
            // phpcs:ignore SlevomatCodingStandard.Classes.ModernClassNameReference.ClassNameReferencedViaFunctionCall, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $className = get_class($statusResponseObject);

            switch ($className) {
                case PaymentResponse::class:
                    $this->paymentResolver->resolve($order, $statusResponseObject);
                    break;
                case RefundResponse::class:
                    if ($order instanceof Order) {
                        // Find a credit memo that matches with the amount provided:
                        // The Worldline API currently does not return the correct merchant reference
                        // for refunds. No matter what merchant reference you provide when requesting
                        // the refund, the response (and the webhooks) will always contain the original
                        // merchant reference of the order.
                        //
                        // We can match it on the amount, because Worldline does not have any other metadata
                        // regarding the order. So if you would have 2 products with the same price, and
                        // only one needs to be refunded, it doesn't matter from Worldline's side which
                        // credit memo you use, since Worldline cannot tell you.
                        //
                        // The only problem this can introduce for the merchant is that it could introduce
                        // a stock issue if the wrong credit memo gets cancelled or something.
                        // But with this shortcoming this solution is the best we can do.
                        $refundedAmount = Data::reformatMagentoAmount(
                            $statusResponseObject->refundOutput->amountOfMoney->amount
                        );
                        $creditMemo = $order
                            ->getCreditmemosCollection()
                            ->addFieldToFilter('base_grand_total', (string) $refundedAmount)
                            ->getFirstItem();

                        if ($creditMemo->getEntityId() === null) {
                            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                            throw new LocalizedException(__('No credit memo found for this order'));
                        }

                        $this->refundResolver->resolve($creditMemo, $statusResponseObject);
                    }
                    break;
                default:
                    // phpcs:ignore SlevomatCodingStandard.Classes.ModernClassNameReference.ClassNameReferencedViaFunctionCall, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                    throw new LocalizedException(__('Unsupported status object: %1', get_class($statusResponseObject)));
            }

            $order->setDataChanges(true);
            $this->orderRepository->save($order);
            $event->setStatus(EventInterface::STATUS_SUCCESS);
            $this->eventRepository->save($event);
        } catch (Throwable $exception) {
            $event->setStatus(EventInterface::STATUS_FAILED);
            $this->eventRepository->save($event);
            $this->orderManagement->addComment(
                $order->getEntityId(),
                $this->orderStatusHistoryFactory->create([
                    'data' => [
                        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $objectType = explode('.', $event->type)[0];
        switch ($objectType) {
            case 'payment':
                return $event->payment;
            case 'refund':
                return $event->refund;
            case 'payout':
            default:
                // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
                throw new RuntimeException("Event type {$event->type} not supported.");
        }
    }
}
