<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Event\Processor;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Api\Data\EventInterface;
use Worldline\Connect\Api\EventManagerInterface;
use Worldline\Connect\Api\EventRepositoryInterface;
use Worldline\Connect\Model\Order\OrderServiceInterface;

/**
 * Process second attempts from the database and mark previous attempts as
 * "IGNORED". This is the case when the customer does multiple attempts
 * with various payment products in the RPP: A cancellation webhook is
 * sent for each previous attempt (in asynchronous order), and we must
 * prevent that Magento acts on those previous attempts (because this
 * would cancel the order).
 *
 * @package Worldline\Connect\Model\Event\Processor
 */
class SecondAttempts
{
    public const MESSAGE_NO_ORDER_FOUND = 'webhook: no order found';

    /**
     * @var EventRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $eventRepository;

    /**
     * @var EventManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $eventManager;

    /**
     * @var OrderServiceInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderService;

    /**
     * @var OrderRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    public function __construct(
        EventRepositoryInterface $eventRepository,
        EventManagerInterface $eventManager,
        OrderServiceInterface $orderService,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->eventManager = $eventManager;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function markOldAttemptsAsIgnored(string $hostedCheckoutId)
    {
        $events = $this->eventManager->getEventsByHostedCheckoutId($hostedCheckoutId);

        // Iteration #1 to get the maximum attempt:
        $maxAttempt = 0;
        foreach ($events->getItems() as $event) {
            if ($json = self::getValidatedPayload($event)) {
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                $maxAttempt = max($maxAttempt, self::getAttempt($json));
            }
        }

        // iteration #2 to mark the webhooks as "IGNORED" that are older attempts:
        foreach ($events->getItems() as $event) {
            if ($json = self::getValidatedPayload($event)) {
                if (self::getAttempt($json) < $maxAttempt) {
                    $event->setStatus(EventInterface::STATUS_IGNORED);
                    $this->eventRepository->save($event);
                    $this->addIgnoredWebhookAttemptCommentToOrder($event);
                }
            }
        }
    }

    private function addIgnoredWebhookAttemptCommentToOrder(EventInterface $event)
    {
        try {
            /** @var Order $order */
            $order = $this->orderService->getByIncrementId($event->getOrderIncrementId());
        } catch (NoSuchEntityException $noSuchEntityException) {
            $this->logger->warning(
                self::MESSAGE_NO_ORDER_FOUND,
                [
                    'increment_id' => $event->getOrderIncrementId(),
                    'event_id' => $event->getEventId(),
                ]
            );
            return;
        }

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $order->addCommentToStatusHistory(__(
            'Ignoring webhook %1: webhook was sent due to a new payment attempt on the RPP',
            $event->getEventId()
        ));

        $this->orderRepository->save($order);
    }

    /**
     * @param EventInterface $event
     * @return bool|array
     */
    public static function getValidatedPayload(EventInterface $event)
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $json = json_decode($event->getPayload(), true);

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (!is_array($json)) {
            return false;
        }

        // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (array_key_exists('payment', $json) &&
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            array_key_exists('id', $json['payment']) &&
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            array_key_exists('hostedCheckoutSpecificOutput', $json['payment']) &&
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            array_key_exists('hostedCheckoutId', $json['payment']['hostedCheckoutSpecificOutput'])
        ) {
            return $json;
        }

        return false;
    }

    /**
     * @param array $json
     * @return int
     */
    public static function getAttempt(array $json): int
    {
        // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (!array_key_exists('payment', $json) ||
            // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.CloseParenthesisLine, SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            !array_key_exists('id', $json['payment'])) {
            return 1;
        }

        $paymentId = $json['payment']['id'];
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $attemptNr = $paymentId[strlen($paymentId) - 1];
        return (int) $attemptNr;
    }
}
