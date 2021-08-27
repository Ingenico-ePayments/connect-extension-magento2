<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Exception;
use Ingenico\Connect\Model\Ingenico\Webhook\Event\MerchantReferenceResolver;
use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\Status\Payment\ResolverInterface;
use Ingenico\Connect\Model\Order\OrderServiceInterface;
use Ingenico\Connect\Model\StatusResponseManager;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Psr\Log\LoggerInterface;

class GetInlinePaymentStatus extends AbstractAction implements ActionInterface
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderServiceInterface
     */
    private $orderService;

    /**
     * @var MerchantReferenceResolver
     */
    private $merchantReferenceResolver;

    /**
     * GetInlinePaymentStatus constructor.
     *
     * @param LoggerInterface $logger
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param ResolverInterface $resolver
     * @param OrderServiceInterface $orderService
     * @param OrderRepositoryInterface $orderRepository
     * @param MerchantReferenceResolver $merchantReferenceResolver
     */
    public function __construct(
        LoggerInterface $logger,
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        ResolverInterface $resolver,
        OrderServiceInterface $orderService,
        OrderRepositoryInterface $orderRepository,
        MerchantReferenceResolver $merchantReferenceResolver
    ) {
        $this->logger = $logger;
        $this->statusResolver = $resolver;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
        $this->merchantReferenceResolver = $merchantReferenceResolver;
        parent::__construct($statusResponseManager, $ingenicoClient, $transactionManager, $config);
    }

    /**
     * @param $referenceId
     * @return Order
     * @throws LocalizedException
     * @throws InvalidArgumentException
     * @throws NoSuchEntityException
     */
    public function process($referenceId)
    {
        $response = $this->ingenicoClient
            ->getIngenicoClient()
            ->merchant($this->ePaymentsConfig->getMerchantId())
            ->payments()
            ->get($referenceId);

        $this->validateResponse($response);

        $incrementId = $this->merchantReferenceResolver->stripReferencePrefix(
            $response->paymentOutput->references->merchantReference
        );

        /**
         * @var Order $order
         */
        $order = $this->orderService->getByIncrementId($incrementId);
        $this->statusResolver->resolve($order, $response);
        $order->addRelatedObject($order->getPayment());

        try {
            $this->orderRepository->save($order);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $order;
    }

    /**
     * @param PaymentResponse $response
     * @throws LocalizedException
     */
    private function validateResponse(PaymentResponse $response)
    {
        if (!$response->paymentOutput) {
            $msg = __('Your payment was rejected or a technical error occured during processing.');
            throw new LocalizedException(__($msg));
        }
    }
}
