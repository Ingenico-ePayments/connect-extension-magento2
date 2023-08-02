<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Order\OrderServiceInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;

class GetInlinePaymentStatus extends AbstractAction implements ActionInterface
{
    /** @var LoggerInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    /**
     * @var ResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusResolver;

    /**
     * @var OrderRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /**
     * @var OrderServiceInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderService;

    /**
     * GetInlinePaymentStatus constructor.
     *
     * @param LoggerInterface $logger
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $worldlineClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param ResolverInterface $resolver
     * @param OrderServiceInterface $orderService
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        LoggerInterface $logger,
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        ResolverInterface $resolver,
        OrderServiceInterface $orderService,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->logger = $logger;
        $this->statusResolver = $resolver;
        $this->orderService = $orderService;
        $this->orderRepository = $orderRepository;
        parent::__construct($statusResponseManager, $worldlineClient, $transactionManager, $config);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function test($referenceId)
    {
        return $this->worldlineClient
            ->getWorldlineClient()
            ->merchant($this->config->getMerchantId())
            ->payments()
            ->get($referenceId)
            ->status;
    }

    /**
     * @param $referenceId
     * @return Order
     * @throws LocalizedException
     * @throws InvalidArgumentException
     * @throws NoSuchEntityException
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function process($referenceId)
    {
        $statusResponse = $this->worldlineClient->worldlinePayment($referenceId);

        $this->validateResponse($statusResponse);

        $incrementId = $statusResponse->paymentOutput->references->merchantReference;

        /**
         * @var Order $order
         */
        $order = $this->orderService->getByIncrementId($incrementId);
        $this->statusResolver->resolve($order, $statusResponse);
        $order->addRelatedObject($order->getPayment());

        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        $status = $statusResponse->status;

        $amount = $order->getBaseGrandTotal();
        switch ($statusResponse->status) {
            case StatusInterface::CAPTURE_REQUESTED:
                $payment->registerCaptureNotification($amount);
                break;
            case StatusInterface::AUTHORIZATION_REQUESTED:
                $payment->registerAuthorizationNotification($amount);
                break;
        }


        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_KEY, $status);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (in_array($status, StatusInterface::APPROVED_STATUSES, true)) {
            $payment->setIsTransactionApproved(true);
//            $payment->capture(null);
        }

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (in_array($status, StatusInterface::DENIED_STATUSES, true)) {
            $payment->cancel();
        }

        try {
            $this->orderRepository->save($order);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
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
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $msg = __('Your payment was rejected or a technical error occured during processing.');
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__($msg));
        }
    }
}
