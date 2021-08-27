<?php

namespace Ingenico\Connect\Model\Ingenico\Webhook;

use Ingenico\Connect\Model\Ingenico\Webhook\Event\MerchantReferenceResolver;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Magento\Framework\Exception\CouldNotSaveException;
use Ingenico\Connect\Api\Data\EventInterface;
use Ingenico\Connect\Api\Data\EventInterfaceFactory;
use Ingenico\Connect\Api\EventRepositoryInterface;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Handler
{
    /**
     * @var EventRepositoryInterface
     */
    private $eventRepository;

    /**
     * @var EventInterfaceFactory
     */
    private $eventFactory;

    /**
     * @var MerchantReferenceResolver
     */
    private $merchantReferenceResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EventRepositoryInterface $eventRepository,
        EventInterfaceFactory $eventFactory,
        MerchantReferenceResolver $merchantReferenceResolver,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->eventFactory = $eventFactory;
        $this->merchantReferenceResolver = $merchantReferenceResolver;
        $this->logger = $logger;
    }

    /**
     * @param WebhooksEvent $event
     * @throws CouldNotSaveException
     * @throws InvalidArgumentException
     * @throws NoSuchEntityException
     */
    public function handle(WebhooksEvent $event): void
    {
        $this->logEventData($event);

        try {
            $this->persistEvent($event);
        } catch (InvalidArgumentException $exception) {
            $this->logger->debug(
                sprintf(
                    'Error with matching event %1$s: %2$s',
                    $event->id,
                    $exception->getMessage()
                )
            );
            throw $exception;
        } catch (CouldNotSaveException $exception) {
            $this->logger->debug(
                sprintf(
                    'Could not save event %1$s in processing queue: %2$s',
                    $event->id,
                    $exception->getMessage()
                )
            );
            throw $exception;
        }
    }

    /**
     * @param WebhooksEvent $event
     */
    private function logEventData(WebhooksEvent $event)
    {
        $jsonArray = json_decode($event->toJson());
        $jsonData = $jsonArray === null ? $event->toJson() : json_encode($jsonArray, JSON_PRETTY_PRINT);
        $this->logger->debug(
            sprintf(
                'Received incoming webhook event with id %1$s:' . PHP_EOL . '%2$s',
                $event->id,
                $this->obfuscate($jsonData)
            )
        );
    }

    private function obfuscate(string $body): string
    {
        return preg_replace(
            '/"expiryDate":(\s?)"(\d{2})(\d{2})"/mUis',
            '"expiryDate":$1"**$3"',
            $body
        );
    }

    /**
     * @param WebhooksEvent $event
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     */
    private function persistEvent(WebhooksEvent $event)
    {
        $eventModel = $this->eventFactory->create(
            [
                'data' => [
                    EventInterface::EVENT_ID => $event->id,
                    EventInterface::ORDER_INCREMENT_ID =>
                        $this->merchantReferenceResolver->stripReferencePrefix(
                            $this->merchantReferenceResolver->getMerchantReference($event)
                        ),
                    EventInterface::PAYLOAD => $event->toJson(),
                    EventInterface::CREATED_TIMESTAMP => $event->created,
                ],
            ]
        );
        $this->eventRepository->save($eventModel);
    }
}
