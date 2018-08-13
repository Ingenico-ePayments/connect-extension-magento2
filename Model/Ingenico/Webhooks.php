<?php

namespace Netresearch\Epayments\Model\Ingenico;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;
use Netresearch\Epayments\Model\Ingenico\Webhooks\EventDataResolverInterface;
use Netresearch\Epayments\Model\Ingenico\Webhooks\HelperAdapter;

class Webhooks
{
    /** @var HelperAdapter */
    private $webhooksHelperAdapter;

    /** @var ResolverInterface */
    private $statusResolver;

    /** @var \Magento\Framework\App\RequestInterface | \Magento\Framework\App\Request\Http */
    private $request;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var SearchCriteriaBuilderFactory */
    private $criteriaBuilderFactory;

    /**
     * Webhooks constructor.
     *
     * @param HelperAdapter $helperAdapter
     * @param ResolverInterface $resolver
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        HelperAdapter $helperAdapter,
        ResolverInterface $resolver,
        OrderRepository $orderRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->webhooksHelperAdapter = $helperAdapter;
        $this->statusResolver = $resolver;
        $this->orderRepository = $orderRepository;
        $this->criteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->request = $request;
    }

    /**
     * Update order status
     *
     * @param EventDataResolverInterface $eventDataResolver
     * @return string
     */
    public function handle(
        EventDataResolverInterface $eventDataResolver
    ) {
        /** @var string $securitySignature */
        $securitySignature = $this->request->getHeader('X-GCS-Signature');
        /** @var string $securityKey */
        $securityKey = $this->request->getHeader('X-GCS-KeyId');

        /** @var \Ingenico\Connect\Sdk\Domain\Webhooks\WebhooksEvent $event */
        $event = $this->webhooksHelperAdapter->unmarshal(
            $this->request->getContent(),
            [
                'X-GCS-Signature' => $securitySignature,
                'X-GCS-KeyId' => $securityKey,
            ]
        );

        $eventResponse = $eventDataResolver->getResponse($event);
        $orderIncrementId = $eventDataResolver->getMerchantOrderReference($event);
        $order = $this->getOrderByIncrementId($orderIncrementId);
        try {
            $this->statusResolver->resolve($order, $eventResponse);
        } catch (\Exception $exception) {
            // @TODO log exception
        }

        $this->orderRepository->save($order);

        return $securitySignature;
    }

    /**
     * @param int $orderIncrementId
     * @return OrderInterface
     */
    private function getOrderByIncrementId($orderIncrementId)
    {
        $criteriaBuilder = $this->criteriaBuilderFactory->create();
        $criteriaBuilder->addFilter('increment_id', $orderIncrementId);
        $orderList = $this->orderRepository->getList($criteriaBuilder->create())->getItems();

        if (count($orderList) !== 1) {
            throw new \RuntimeException('System can not load order mentioned in the Event.');
        }
        return array_shift($orderList);
    }
}
