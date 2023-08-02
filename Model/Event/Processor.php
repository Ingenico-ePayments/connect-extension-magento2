<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Event;

use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEventFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo\CreationArguments;
use Magento\Sales\Model\RefundOrder;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use Worldline\Connect\Api\Data\EventInterface;
use Worldline\Connect\Api\EventRepositoryInterface;
use Worldline\Connect\Helper\Data;
use Worldline\Connect\Model\Order\OrderServiceInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface as PaymentResolverInterface;
use Worldline\Connect\Model\Worldline\Status\Refund\ResolverInterface as RefundResolverInterface;

use function __;
use function sprintf;

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

    /**
     * @var RefundOrder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $refundOrder;

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
        RefundOrder $refundOrder
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
        $this->refundOrder = $refundOrder;
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
    // phpcs:ignore Generic.Metrics.NestingLevel.MaxExceeded, SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    private function processEvent(EventInterface $event): void
    {
        $webhookEvent = $this->webhookEventFactory->create()->fromJson($event->getPayload());

        if ($this->checkEndpointTest($webhookEvent)) {
            return;
        }

        if ($webhookEvent->payment !== null) {
            $order = $this->orderService->getByIncrementId(
                $webhookEvent->payment->paymentOutput->references->merchantReference
            );
            $this->paymentResolver->resolve($order, $webhookEvent->payment);
        } elseif ($webhookEvent->refund !== null) {
            $order = $this->orderService->getByIncrementId(
                $webhookEvent->refund->refundOutput->references->merchantReference
            );
            $refundedAmount = Data::reformatMagentoAmount(
                $webhookEvent->refund->refundOutput->amountOfMoney->amount
            );

            $creditMemo = $this->getOrCreateCreditMemo($order, $refundedAmount);
            if ($creditMemo->getEntityId() === null) {
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                throw new LocalizedException(__('No credit memo found for this order'));
            }

            $this->refundResolver->resolve($creditMemo, $webhookEvent->refund);
        } else {
            throw new RuntimeException(sprintf('Event type %s not supported.', $webhookEvent->type));
        }

        $order->addCommentToStatusHistory($event->getPayload());
        $order->setDataChanges(true);

        $this->orderRepository->save($order);
    }

    /**
     * Detects Worldline Webhook test request.
     * When a request is an endpoint test, it should not be processed.
     *
     * @param WebhooksEvent $event
     * @return bool
     */
    private function checkEndpointTest(WebhooksEvent $event): bool
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return strpos((string) $event->id, 'TEST') === 0;
    }

    private function getOrCreateCreditMemo(Order $order, float $refundedAmount): CreditmemoInterface
    {
        $creditMemo = $this->getCreditMemoByRefundedAmount($order, $refundedAmount);
        if ($creditMemo->getEntityId() !== null) {
            return $creditMemo;
        }

        $this->createCreditMemoByRefundedAmount($order, $refundedAmount);

        return $this->getCreditMemoByRefundedAmount($order, $refundedAmount);
    }

    private function getCreditMemoByRefundedAmount(Order $order, float $refundedAmount): CreditmemoInterface
    {
        /** @var CreditmemoInterface $creditMemo */
        $creditMemo = $order
            ->getCreditmemosCollection()
            ->addFieldToFilter('base_grand_total', (string) $refundedAmount)
            ->getFirstItem();

        return $creditMemo;
    }

    private function createCreditMemoByRefundedAmount(Order $order, float $refundedAmount): void
    {
        $creditMemoCreationArguments = new CreationArguments();
        $creditMemoCreationArguments->setAdjustmentPositive($refundedAmount);
        $creditMemoCreationArguments->setShippingAmount(0.0);

        $this->refundOrder->execute(
            $order->getId(),
            [],
            false,
            false,
            null,
            $creditMemoCreationArguments
        );
    }
}
