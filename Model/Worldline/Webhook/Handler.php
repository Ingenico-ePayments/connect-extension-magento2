<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Webhook;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use DateTimeImmutable;
use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Api\Data\EventInterface;
use Worldline\Connect\Api\Data\EventInterfaceFactory;
use Worldline\Connect\Api\EventRepositoryInterface;

class Handler
{
    /**
     * @var EventRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $eventRepository;

    /**
     * @var EventInterfaceFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $eventFactory;

    /**
     * @var LoggerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    public function __construct(
        EventRepositoryInterface $eventRepository,
        EventInterfaceFactory $eventFactory,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->eventFactory = $eventFactory;
        $this->logger = $logger;
    }

    /**
     * @param WebhooksEvent $event
     * @param DateTimeImmutable $date
     * @throws CouldNotSaveException
     * @throws InvalidArgumentException
     */
    public function handle(WebhooksEvent $event, DateTimeImmutable $date): void
    {
        $this->logEventData($event);

        try {
            $this->persistEvent($event, $date);
        } catch (InvalidArgumentException $exception) {
            $this->logger->debug(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                sprintf(
                    'Error with matching event %1$s: %2$s',
                    $event->id,
                    $exception->getMessage()
                )
            );
            throw $exception;
        } catch (CouldNotSaveException $exception) {
            $this->logger->debug(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $jsonArray = json_decode($event->toJson());
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $jsonData = $jsonArray === null ? $event->toJson() : json_encode($jsonArray, JSON_PRETTY_PRINT);
        $this->logger->debug(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            sprintf(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'Received incoming webhook event with id %1$s:' . PHP_EOL . '%2$s',
                $event->id,
                $this->obfuscate($jsonData)
            )
        );
    }

    private function obfuscate(string $body): string
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return preg_replace(
            '/"expiryDate":(\s?)"(\d{2})(\d{2})"/mUis',
            '"expiryDate":$1"**$3"',
            $body
        );
    }

    /**
     * @param WebhooksEvent $event
     * @param DateTimeImmutable $date
     * @throws CouldNotSaveException
     */
    private function persistEvent(WebhooksEvent $event, DateTimeImmutable $date)
    {
        $this->eventRepository->save($this->eventFactory->create([
            'data' => [
                EventInterface::PAYLOAD => $event->toJson(),
                EventInterface::CREATED_TIMESTAMP => $date->format('Y-m-d H:i:s.u'),
            ],
        ]));
    }
}
