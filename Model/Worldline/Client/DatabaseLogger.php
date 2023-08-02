<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Client;

use DateTimeImmutable;
use Exception;
use Ingenico\Connect\Sdk\CommunicatorLogger;
use Worldline\Connect\Api\Data\EventInterface;
use Worldline\Connect\Api\Data\EventInterfaceFactory;
use Worldline\Connect\Api\EventRepositoryInterface;

class DatabaseLogger implements CommunicatorLogger
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
        private readonly EventInterfaceFactory $eventFactory,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function log($message)
    {
        $this->persist($message, new DateTimeImmutable('now'), EventInterface::STATUS_SUCCESS);
    }

    /**
     * {@inheritdoc}
     */
    public function logException($message, Exception $exception)
    {
        $this->persist($message, new DateTimeImmutable('now'), EventInterface::STATUS_FAILED);
    }

    private function persist(string $message, DateTimeImmutable $date, int $status): void
    {
        $this->eventRepository->save($this->eventFactory->create([
            'data' => [
                EventInterface::PAYLOAD => $message,
                EventInterface::CREATED_TIMESTAMP => $date->format('Y-m-d H:i:s.u'),
                EventInterface::STATUS => $status,
            ],
        ]));
    }
}
