<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Order\OrderServiceInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface;

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
        $this->orderRepository = $orderRepository;
        parent::__construct($statusResponseManager, $worldlineClient, $transactionManager, $config);
    }

    /**
     * @throws LocalizedException
     * @throws InvalidArgumentException
     * @throws NoSuchEntityException
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint, Generic.Metrics.CyclomaticComplexity.TooHigh
    public function process(Order $order, Payment $payment)
    {
        $this->validateResponse($payment);

        /** @var OrderPayment $orderPayment */
        $orderPayment = $order->getPayment();

        $this->statusResolver->resolve($order, $payment);

        $order->addRelatedObject($orderPayment);

        $orderPayment->setAdditionalInformation(Config::PAYMENT_ID_KEY, $payment->id);
        $orderPayment->setAdditionalInformation(Config::PAYMENT_STATUS_KEY, $payment->status);
        $orderPayment->setAdditionalInformation(Config::PAYMENT_STATUS_CODE_KEY, $payment->statusOutput->statusCode);
    }

    /**
     * @throws LocalizedException
     */
    private function validateResponse(Payment $response)
    {
        if (!$response->paymentOutput) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $msg = __('Your payment was rejected or a technical error occured during processing.');
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__($msg));
        }
    }
}
