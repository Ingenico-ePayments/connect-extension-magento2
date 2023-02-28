<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\Domain\Hostedcheckout\GetHostedCheckoutResponse;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Helper\Data;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Order\OrderServiceInterface;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;

use function __;

/**
 * Uses to update Magento Order state/status after payment creation via HostedCheckout Payment method.
 *
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__hostedcheckouts__hostedCheckoutId__get
 */
class GetHostedCheckoutStatus implements ActionInterface
{
    public const PAYMENT_CREATED = 'PAYMENT_CREATED';
    public const IN_PROGRESS = 'IN_PROGRESS';
    public const PAYMENT_STATUS_CATEGORY_SUCCESSFUL = 'SUCCESSFUL';
    public const PAYMENT_STATUS_CATEGORY_UNKNOWN = 'STATUS_UNKNOWN';
    public const PAYMENT_STATUS_CATEGORY_REJECTED = 'REJECTED';
    public const PAYMENT_OUTPUT_SHOW_INSTRUCTIONS = 'SHOW_INSTRUCTIONS';
    public const CANCELLED_BY_CONSUMER = 'CANCELLED_BY_CONSUMER';

    /**
     * @var LoggerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    /**
     * @var ClientInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $client;

    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $ePaymentsConfig;

    /**
     * @var Http
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $request;

    /**
     * @var OrderSender
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderSender;

    /**
     * @var ResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusResolver;

    /**
     * @var OrderRepository
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /**
     * @var OrderServiceInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderService;

    /**
     * @param LoggerInterface $logger
     * @param ClientInterface $client
     * @param ConfigInterface $ePaymentsConfig
     * @param Http $request
     * @param OrderSender $orderSender
     * @param ResolverInterface $statusResolver
     * @param OrderRepository $orderRepository
     * @param OrderServiceInterface $orderService
     */
    public function __construct(
        LoggerInterface $logger,
        ClientInterface $client,
        ConfigInterface $ePaymentsConfig,
        Http $request,
        OrderSender $orderSender,
        ResolverInterface $statusResolver,
        OrderRepository $orderRepository,
        OrderServiceInterface $orderService
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->request = $request;
        $this->orderSender = $orderSender;
        $this->statusResolver = $statusResolver;
        $this->orderRepository = $orderRepository;
        $this->orderService = $orderService;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function test(string $hostedCheckoutId)
    {
        return $this->client->getHostedCheckout($hostedCheckoutId)->createdPaymentOutput->payment->status;
    }

    /**
     * Load HostedCheckout instance from API and apply it to corresponding order
     *
     * @param string $hostedCheckoutId
     * @return OrderInterface|null
     * @throws LocalizedException
     */
    public function process(string $hostedCheckoutId)
    {
        $order = $this->getOrder($hostedCheckoutId);

        $statusResponse = $this->client->getHostedCheckout($hostedCheckoutId);

        $this->processOrder($statusResponse, $order);

        try {
            $this->orderRepository->save($order);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $order;
    }

    /**
     * Handles rejected or faulty orders by checking paymentStatusCategory, will escalate through exception
     *
     * @param GetHostedCheckoutResponse $statusResponse
     * @param OrderInterface $order
     * @throws LocalizedException
     */
    private function checkPaymentStatusCategory(
        GetHostedCheckoutResponse $statusResponse,
        OrderInterface $order
    ) {
        $createdPaymentOutput = $statusResponse->createdPaymentOutput;
        if ($createdPaymentOutput->paymentStatusCategory === self::PAYMENT_STATUS_CATEGORY_REJECTED) {
            $status = $createdPaymentOutput->payment->status;

            $info = $this->ePaymentsConfig->getPaymentStatusInfo($status);
            /** @var string $message */
            if ($info) {
                $msg = __('Payment error:') . ' ' . $info;
            } else {
                $msg = __('Your payment was rejected or a technical error occured during processing.');
            }

            $order->registerCancellation();
            // phpcs:ignore Squiz.Strings.DoubleQuoteUsage.ContainsVar
            $order->addCommentToStatusHistory("<b>Payment error, status</b><br />{$status} : $msg");
            $this->orderRepository->save($order);
            throw new LocalizedException(__($msg));
        }
    }

    /**
     * Check return mac
     *
     * @param OrderInterface $order
     * @throws LocalizedException
     */
    private function checkReturnmac(OrderInterface $order)
    {
        $worldlineReturnmac = $this->request->get('RETURNMAC');
        if ($worldlineReturnmac === null) {
            return;
        }
        $orderReturnmac = $order->getPayment()->getAdditionalInformation('worldline_returnmac');
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
        if ($worldlineReturnmac != $orderReturnmac) {
            throw new LocalizedException(__('RETURNMAC doesn\'t match.'));
        }
    }

    /**
     * Process order
     *
     * @param Order $order
     * @param GetHostedCheckoutResponse $statusResponse
     * @throws LocalizedException
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    private function processOrder(GetHostedCheckoutResponse $statusResponse, Order $order)
    {
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        if ($statusResponse->status === self::CANCELLED_BY_CONSUMER) {
            $order->cancel();
            return;
        }

        if (!$statusResponse->createdPaymentOutput) {
            $msg = __('Your payment was rejected or a technical error occured during processing.');
            throw new LocalizedException(__($msg));
        }

        $worldlinePaymentId = $statusResponse->createdPaymentOutput->payment->id;
        $worldlinePaymentStatus = $statusResponse->createdPaymentOutput->payment->status;
        $worldlinePaymentStatusCode = $statusResponse->createdPaymentOutput->payment->statusOutput->statusCode;

        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found, PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine
        if (isset($statusResponse->createdPaymentOutput->displayedData)
            && $statusResponse->createdPaymentOutput->displayedData->displayedDataType
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
            == self::PAYMENT_OUTPUT_SHOW_INSTRUCTIONS
        ) {
            $payment->setAdditionalInformation(
                Config::PAYMENT_SHOW_DATA_KEY,
                $statusResponse->createdPaymentOutput->displayedData->toJson()
            );
        }

        $payment->setAdditionalInformation(
            Config::PRODUCT_TOKENIZE_KEY,
            $statusResponse->createdPaymentOutput->tokens !== null ? '1' : '0'
        );

        $statusChanged = $this->statusResolver->resolve($order, $statusResponse->createdPaymentOutput->payment);

        $payment->setAdditionalInformation(Config::PAYMENT_ID_KEY, $worldlinePaymentId);
        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_KEY, $worldlinePaymentStatus);
        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_CODE_KEY, $worldlinePaymentStatusCode);

        if ($statusChanged) {
            $amount = $statusResponse->createdPaymentOutput->payment->paymentOutput->amountOfMoney->amount;
            switch ($statusResponse->createdPaymentOutput->payment->status) {
                case StatusInterface::CAPTURE_REQUESTED:
                    $payment->registerCaptureNotification(Data::reformatMagentoAmount($amount));
                    break;
                case StatusInterface::AUTHORIZATION_REQUESTED:
                    $payment->registerAuthorizationNotification(Data::reformatMagentoAmount($amount));
                    break;
            }
        }
    }

    /**
     * @param string $hostedCheckoutId
     * @return Order
     * @throws LocalizedException
     */
    private function getOrder(string $hostedCheckoutId): Order
    {
        try {
            /** @var Order $order */
            $order = $this->orderService->getByHostedCheckoutId($hostedCheckoutId);
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(
                __('There was no order found for RPP (hosted checkout ID: %1)', $hostedCheckoutId)
            );
        }

        return $order;
    }
}
