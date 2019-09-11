<?php

namespace Ingenico\Connect\Model\Ingenico;

use Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Ingenico\Connect\Api\Data\EventInterface;
use Ingenico\Connect\Api\Data\EventInterfaceFactory;
use Ingenico\Connect\Api\EventRepositoryInterface;
use Ingenico\Connect\Model\Ingenico\Status\ResolverInterface;
use Ingenico\Connect\Model\Ingenico\Webhooks\EventDataResolverInterface;
use Ingenico\Connect\Model\Ingenico\Webhooks\HelperAdapter;
use Psr\Log\LoggerInterface;

class Webhooks
{
    /**
     * @var HelperAdapter
     */
    private $webhooksHelperAdapter;

    /**
     * @var RequestInterface | \Magento\Framework\App\Request\Http
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
     * Webhooks constructor.
     *
     * @param HelperAdapter $webhooksHelperAdapter
     * @param ResolverInterface $statusResolver
     * @param \Magento\Framework\App\Request\Http|RequestInterface $request
     * @param EventRepositoryInterface $eventRepository
     * @param EventInterfaceFactory $eventFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        HelperAdapter $webhooksHelperAdapter,
        RequestInterface $request,
        EventRepositoryInterface $eventRepository,
        EventInterfaceFactory $eventFactory,
        LoggerInterface $logger
    ) {
        $this->webhooksHelperAdapter = $webhooksHelperAdapter;
        $this->request = $request;
        $this->eventRepository = $eventRepository;
        $this->eventFactory = $eventFactory;
        $this->logger = $logger;
    }

    /**
     * @param EventDataResolverInterface $eventDataResolver
     * @return string
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function handle(EventDataResolverInterface $eventDataResolver)
    {
        /** @var string $securitySignature */
        $securitySignature = $this->request->getHeader('X-GCS-Signature');
        /** @var string $securityKey */
        $securityKey = $this->request->getHeader('X-GCS-KeyId');

        $event = $this->webhooksHelperAdapter->unmarshal(
            $this->request->getContent(),
            [
                'X-GCS-Signature' => $securitySignature,
                'X-GCS-KeyId' => $securityKey,
            ]
        );

        if ($this->checkEndpointTest($event)) {
            return $securitySignature;
        }

        $this->logger->debug(
            "Received incoming webhook event with id {$event->id}:\n
            {$event->toJson()}"
        );

        try {
            $orderIncrementId = $eventDataResolver->getMerchantOrderReference($event);
            $eventModel = $this->eventFactory->create(
                [
                    'data' => [
                        EventInterface::EVENT_ID => $event->id,
                        EventInterface::ORDER_INCREMENT_ID => $orderIncrementId,
                        EventInterface::PAYLOAD => $event->toJson(),
                        EventInterface::CREATED_TIMESTAMP => $event->created
                    ],
                ]
            );
            $this->eventRepository->save($eventModel);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->debug("Error with matching event {$event->id}: {$exception->getMessage()}");

            return $exception->getMessage();
        } catch (CouldNotSaveException $exception) {
            $this->logger->debug("Could not save event {$event->id} in processing queue: {$exception->getMessage()}");

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
}
