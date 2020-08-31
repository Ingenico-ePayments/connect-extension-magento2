<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\MerchantReference;
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
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * GetInlinePaymentStatus constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param ResolverInterface $resolver
     * @param OrderServiceInterface $orderService
     * @param OrderRepositoryInterface $orderRepository
     * @param MerchantReference $merchantReference
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
        MerchantReference $merchantReference
    ) {
        $this->logger = $logger;
        $this->statusResolver = $resolver;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
        $this->merchantReference = $merchantReference;
        parent::__construct($statusResponseManager, $ingenicoClient, $transactionManager, $config);
    }

    /**
     * @param $referenceId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process($referenceId)
    {
        $response = $this->ingenicoClient
            ->getIngenicoClient()
            ->merchant($this->ePaymentsConfig->getMerchantId())
            ->payments()
            ->get($referenceId);

        $this->validateResponse($response);

        $incrementId = $this->merchantReference->extractOrderReference(
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
        } catch (\Exception $exception) {
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
