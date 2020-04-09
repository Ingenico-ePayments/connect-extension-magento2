<?php

namespace Ingenico\Connect\Model\Ingenico\Webhook;

use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use InvalidArgumentException;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Ingenico\Connect\Api\Data\EventInterface;
use Ingenico\Connect\Api\Data\EventInterfaceFactory;
use Ingenico\Connect\Api\EventRepositoryInterface;
use Ingenico\Connect\Model\Ingenico\Status\ResolverInterface;
use Psr\Log\LoggerInterface;
use Ingenico\Connect\Model\Ingenico\Webhook\Event\ResolverInterface as EventResolverInterface;

class Handler
{
    /**
     * @var Unmarshaller
     */
    private $unmarshaller;

    /**
     * @var RequestInterface|Http
     */
    private $request;

    /**
     * @var EventRepositoryInterface
     */
    private $eventRepository;

    /**
     * @var EventInterfaceFactory
     */
    private $eventFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Handler constructor.
     *
     * @param Unmarshaller $unmarshaller
     * @param RequestInterface $request
     * @param EventRepositoryInterface $eventRepository
     * @param EventInterfaceFactory $eventFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Unmarshaller $unmarshaller,
        RequestInterface $request,
        EventRepositoryInterface $eventRepository,
        EventInterfaceFactory $eventFactory,
        LoggerInterface $logger
    ) {
        $this->unmarshaller = $unmarshaller;
        $this->request = $request;
        $this->eventRepository = $eventRepository;
        $this->eventFactory = $eventFactory;
        $this->logger = $logger;
    }

    /**
     * @param EventResolverInterface $eventDataResolver
     * @return string
     * @throws CouldNotSaveException
     */
    public function handle(EventResolverInterface $eventDataResolver)
    {
        /** @var string $securitySignature */
        $securitySignature = $this->request->getHeader('X-GCS-Signature');
        /** @var string $securityKey */
        $securityKey = $this->request->getHeader('X-GCS-KeyId');

        $event = $this->unmarshaller->unmarshal(
            $this->request->getContent(),
            [
                'X-GCS-Signature' => $securitySignature,
                'X-GCS-KeyId' => $securityKey,
            ]
        );

        if ($this->checkEndpointTest($event)) {
            return $securitySignature;
        }

        $this->logEventData($event);

        try {
            $orderIncrementId = $eventDataResolver->getMerchantOrderReference($event);
            $this->persistEvent($event, $orderIncrementId);
        } catch (InvalidArgumentException $exception) {
            $this->logger->debug(
                sprintf(
                    'Error with matching event %1$s: %2$s',
                    $event->id,
                    $exception->getMessage()
                )
            );
            return $exception->getMessage();
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

        return $securitySignature;
    }

    /**
     * Detects Ingenico Webhook test request.
     * When a request is an endpoint test, it should not be processed.
     *
     * @param WebhooksEvent $event
     * @return bool
     */
    private function checkEndpointTest(WebhooksEvent $event)
    {
        return strpos($event->id, 'TEST') === 0;
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
     * @param string $orderIncrementId
     * @throws CouldNotSaveException
     */
    private function persistEvent(WebhooksEvent $event, string $orderIncrementId)
    {
        $eventModel = $this->eventFactory->create(
            [
                'data' => [
                    EventInterface::EVENT_ID => $event->id,
                    EventInterface::ORDER_INCREMENT_ID => $orderIncrementId,
                    EventInterface::PAYLOAD => $event->toJson(),
                    EventInterface::CREATED_TIMESTAMP => $event->created,
                ],
            ]
        );
        $this->eventRepository->save($eventModel);
    }
}
