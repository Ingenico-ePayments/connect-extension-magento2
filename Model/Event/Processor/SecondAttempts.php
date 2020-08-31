<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Event\Processor;

use Ingenico\Connect\Api\Data\EventInterface;
use Ingenico\Connect\Api\EventManagerInterface;
use Ingenico\Connect\Api\EventRepositoryInterface;

/**
 * Process second attempts from the database and mark previous attempts as
 * "IGNORED". This is the case when the customer does multiple attempts
 * with various payment products in the RPP: A cancellation webhook is
 * sent for each previous attempt (in asynchronous order), and we must
 * prevent that Magento acts on those previous attempts (because this
 * would cancel the order).
 *
 * @package Ingenico\Connect\Model\Event\Processor
 */
class SecondAttempts
{
    /**
     * @var EventRepositoryInterface
     */
    private $eventRepository;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    public function __construct(
        EventRepositoryInterface $eventRepository,
        EventManagerInterface $eventManager
    ) {
        $this->eventRepository = $eventRepository;
        $this->eventManager = $eventManager;
    }

    public function markOldAttemptsAsIgnored(string $hostedCheckoutId)
    {
        $events = $this->eventManager->getEventsByHostedCheckoutId($hostedCheckoutId);

        // Iteration #1 to get the maximum attempt:
        $maxAttempt = 0;
        foreach ($events->getItems() as $event) {
            if ($json = self::getValidatedPayload($event)) {
                $maxAttempt = max($maxAttempt, self::getAttempt($json));
            }
        }

        // iteration #2 to mark the webhooks as "IGNORED" that are older attempts:
        foreach ($events->getItems() as $event) {
            if ($json = self::getValidatedPayload($event)) {
                if (self::getAttempt($json) < $maxAttempt) {
                    $event->setStatus(EventInterface::STATUS_IGNORED);
                    $this->eventRepository->save($event);
                }
            }
        }
    }

    /**
     * @param EventInterface $event
     * @return bool|array
     */
    public static function getValidatedPayload(EventInterface $event)
    {
        $json = json_decode($event->getPayload(), true);

        if (!is_array($json)) {
            return false;
        }

        if (array_key_exists('payment', $json) &&
            array_key_exists('id', $json['payment']) &&
            array_key_exists('hostedCheckoutSpecificOutput', $json['payment']) &&
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
        if (!array_key_exists('payment', $json) ||
            !array_key_exists('id', $json['payment'])) {
            return 1;
        }

        $paymentId = $json['payment']['id'];
        $attemptNr = $paymentId[strlen($paymentId) - 1];
        return (int) $attemptNr;
    }
}
