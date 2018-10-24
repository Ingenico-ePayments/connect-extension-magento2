<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;
use Netresearch\Epayments\Model\Order\OrderServiceInterface;

class GetInlinePaymentStatus extends AbstractAction implements ActionInterface
{

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
     * GetInlinePaymentStatus constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param ResolverInterface $resolver
     * @param OrderServiceInterface $orderService
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        ResolverInterface $resolver,
        OrderServiceInterface $orderService,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->statusResolver = $resolver;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
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

        $incrementId = $response->paymentOutput->references->merchantReference;
        /**
         * @var Order $order
         */
        $order = $this->orderService->getByIncrementId($incrementId);
        $this->statusResolver->resolve($order, $response);
        $order->addRelatedObject($order->getPayment());
        $this->orderRepository->save($order);

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
