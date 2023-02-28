<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\OrderUpdate;

use Magento\Framework\Logger\Monolog;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\OrderRepository;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface;

class Order implements OrderInterface
{
    public const STATUS_WAIT = 'wait';
    public const STATUS_FINISHED = 'finished';

    /**
     * @var ResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusResolver;

    /**
     * @var SchedulerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $scheduler;

    /**
     * @var ClientInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $worldlineClient;

    /**
     * @var Monolog
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    /**
     * @var OrderRepository
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /**
     * @var DateTime
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $dateTime;

    /**
     * @param ResolverInterface $resolver
     * @param SchedulerInterface $scheduler
     * @param ClientInterface $client
     * @param Monolog $logger
     * @param ConfigInterface $config
     * @param OrderRepository $orderRepository
     * @param DateTime $dateTime
     */
    public function __construct(
        ResolverInterface $resolver,
        SchedulerInterface $scheduler,
        ClientInterface $client,
        Monolog $logger,
        ConfigInterface $config,
        OrderRepository $orderRepository,
        DateTime $dateTime
    ) {
        $this->statusResolver = $resolver;
        $this->scheduler = $scheduler;
        $this->worldlineClient = $client;
        $this->logger = $logger;
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function process(\Magento\Sales\Model\Order $order)
    {
        $orderId = $order->getEntityId();
        $paymentId = $this->readPaymentId($order);
        if ($paymentId === '') {
            // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
            $this->logger->info("--- Order $orderId skipped, no Worldline Payment ID found.");

            return;
        }

        // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
        $this->logger->info("--- Order $orderId with Worldline Payment ID $paymentId");

        // skip order if it's not time to pull
        if (!$this->scheduler->timeForAttempt($order)) {
            // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
            $this->logger->info("Order $orderId skipped, not due yet");

            return;
        }

        // phpcs:ignore Generic.Commenting.Fixme.TaskFound
        // @fixme: this will only process payments, not refunds
        // also: not sure if this code is needed at all...
        // this has something to do with the webhook workaround cron :-/
        try {
            // call worldline
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
            /** @var \Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse $response */
            $response = $this->getWorldlinePayment($paymentId, $order->getStoreId());

            // update order status
            // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
            $this->logger->info("worldline status is {$response->status}");
            $this->statusResolver->resolve($order, $response);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        // add last attempt time
        $order->setOrderUpdateApiLastAttemptTime($this->dateTime->timestamp());

        // check if time to WR
        if ($this->scheduler->timeForWr($order)) {
            $order->setOrderUpdateWrStatus(
                self::STATUS_WAIT
            );
        }

        $this->orderRepository->save($order);
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Api call to Worldline to get payment details
     *
     * @param string $worldlinePaymentId
     * @param string|int $scopeId
     * @return \Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    private function getWorldlinePayment($worldlinePaymentId, $scopeId)
    {
        $response = $this->worldlineClient->getWorldlineClient($scopeId)
            ->merchant($this->config->getMerchantId($scopeId))
            ->payments()
            ->get($worldlinePaymentId);

        return $response;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Extract AdditionalInformation JSON and try to read Worldline Payment ID.
     * Returns empty string on failure.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    private function readPaymentId(\Magento\Sales\Model\Order $order)
    {
        $paymentId = '';
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $paymentData = json_decode($order->getAdditionalInformation(), true);
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        if (isset($paymentData[Config::PAYMENT_ID_KEY])) {
            $paymentId = $paymentData[Config::PAYMENT_ID_KEY];
        }

        return $paymentId;
    }
}
