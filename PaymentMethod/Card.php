<?php

namespace Ingenico\Connect\PaymentMethod;

use Ingenico\Connect\Api\OrderPaymentManagementInterface;
use Ingenico\Connect\Gateway\Command\ApiErrorHandler;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\Action\ApprovePayment;
use Ingenico\Connect\Model\Ingenico\Action\CapturePayment;
use Ingenico\Connect\Model\Ingenico\Action\MerchantAction;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\FraudFieldsBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\MerchantBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\OrderBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card\ThreeDSecureBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Payment\ResolverInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequestFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificInputFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardRecurrenceDetailsFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as PaymentDefinition;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;

class Card implements CommandInterface
{
    const TRANSACTION_CHANNEL = 'ECOMMERCE';

    /**
     * @var CreatePaymentRequestFactory
     */
    private $createPaymentRequestFactory;

    /**
     * @var OrderBuilder
     */
    private $orderBuilder;

    /**
     * @var MerchantBuilder
     */
    private $merchantBuilder;

    /**
     * @var FraudFieldsBuilder
     */
    private $fraudFieldsBuilder;

    /**
     * @var CardPaymentMethodSpecificInputFactory
     */
    private $cardPaymentMethodSpecificInputFactory;

    /**
     * @var CardRecurrenceDetailsFactory
     */
    private $cardRecurrenceDetailsFactory;

    /**
     * @var ThreeDSecureBuilder
     */
    private $threeDSecureBuilder;

    /**
     * @var CapturePayment
     */
    private $capturePayment;

    /**
     * @var ApprovePayment
     */
    private $approvePayment;

    /**
     * @var ApiErrorHandler
     */
    private $apiErrorHandler;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var MerchantAction
     */
    private $merchantAction;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Order\Email\Sender\OrderSender
     */
    private $orderSender;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var OrderPaymentManagementInterface
     */
    private $orderPaymentManagement;

    /**
     * @param CreatePaymentRequestFactory $createPaymentRequestFactory
     * @param OrderBuilder $orderBuilder
     * @param MerchantBuilder $merchantBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     * @param CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory
     * @param CardRecurrenceDetailsFactory $cardRecurrenceDetailsFactory
     * @param ThreeDSecureBuilder $threeDSecureBuilder
     * @param CapturePayment $capturePayment
     * @param ApprovePayment $approvePayment
     * @param ApiErrorHandler $apiErrorHandler
     * @param ClientInterface $client
     * @param MerchantAction $merchantAction
     * @param ResolverInterface $statusResolver
     * @param OrderRepositoryInterface $orderRepository
     * @param Order\Email\Sender\OrderSender $orderSender
     * @param LoggerInterface $logger
     * @param OrderPaymentManagementInterface $orderPaymentManagement
     */
    public function __construct(
        CreatePaymentRequestFactory $createPaymentRequestFactory,
        OrderBuilder $orderBuilder,
        MerchantBuilder $merchantBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        CardRecurrenceDetailsFactory $cardRecurrenceDetailsFactory,
        ThreeDSecureBuilder $threeDSecureBuilder,
        CapturePayment $capturePayment,
        ApprovePayment $approvePayment,
        ApiErrorHandler $apiErrorHandler,
        ClientInterface $client,
        MerchantAction $merchantAction,
        OrderRepositoryInterface $orderRepository,
        Order\Email\Sender\OrderSender $orderSender,
        LoggerInterface $logger,
        ResolverInterface $statusResolver,
        OrderPaymentManagementInterface $orderPaymentManagement
    ) {
        $this->createPaymentRequestFactory = $createPaymentRequestFactory;
        $this->orderBuilder = $orderBuilder;
        $this->merchantBuilder = $merchantBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->cardRecurrenceDetailsFactory = $cardRecurrenceDetailsFactory;
        $this->threeDSecureBuilder = $threeDSecureBuilder;
        $this->capturePayment = $capturePayment;
        $this->approvePayment = $approvePayment;
        $this->apiErrorHandler = $apiErrorHandler;
        $this->client = $client;
        $this->merchantAction = $merchantAction;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->statusResolver = $statusResolver;
        $this->orderPaymentManagement = $orderPaymentManagement;
    }

    /**
     * @param mixed[] $commandSubject
     * @return void
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        /** @var Payment $payment */
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'];
        $order = $payment->getOrder();

        if ($order->getEntityId() === null) {
            $this->createPaymentByOrder($payment, $order);
            return;
        }

        $this->handleStatusUpdate($payment, $amount);
    }


    /**
     * @param Order $order
     * @return void
     * @throws LocalizedException
     */
    private function createPaymentByOrder(Payment $payment, Order $order): void
    {
        $method = $payment->getMethodInstance();
        $request = $this->createPaymentRequestFactory->create();
        $request->order = $this->orderBuilder->create($order);
        $request->merchant = $this->merchantBuilder->create($order);
        $request->fraudFields = $this->fraudFieldsBuilder->create($order);
        $request->encryptedCustomerInput = $order->getPayment()->getAdditionalInformation('input');

        $input = $this->cardPaymentMethodSpecificInputFactory->create();
        $input->threeDSecure = $this->threeDSecureBuilder->create($order);
        $input->transactionChannel = self::TRANSACTION_CHANNEL;
        $input->paymentProductId = $order->getPayment()->getAdditionalInformation('product');
        $input->requiresApproval = $method->getConfigPaymentAction() === MethodInterface::ACTION_AUTHORIZE;
        $input->tokenize = $order->getPayment()->getAdditionalInformation('tokenize');

        $orderPaymentExtension = $payment->getExtensionAttributes();
        if ($orderPaymentExtension !== null) {
            $paymentToken = $orderPaymentExtension->getVaultPaymentToken();
            if ($paymentToken !== null) {
                $input->token = $paymentToken->getGatewayToken();
            }
        }

        $request->cardPaymentMethodSpecificInput = $input;

        $response = $this->client->createPayment($request);
        if ($response->merchantAction && $response->merchantAction->actionType) {
            $this->merchantAction->handle($order, $response->merchantAction);
        }

        $paymentResponse = $response->payment;
        if ($paymentResponse !== null) {
            $this->statusResolver->resolve($order, $paymentResponse);
            $this->processOrder($paymentResponse, $order);
        }
    }

    /**
     * @param Payment $payment
     * @param mixed $amount
     * @return void
     * @throws CommandException
     * @throws LocalizedException
     */
    private function handleStatusUpdate(Payment $payment, $amount): void
    {
        $status = $this->orderPaymentManagement->getIngenicoPaymentStatus($payment);

        try {
            switch ($status) {
                case StatusInterface::PENDING_CAPTURE:
                    $this->capturePayment->process($payment->getOrder(), $amount);
                    break;
                case StatusInterface::PENDING_APPROVAL:
                    $this->approvePayment->process($payment->getOrder(), $amount);
                    break;
                case StatusInterface::CAPTURE_REQUESTED:
                    throw new CommandException(__('Payment is already captured'));
                default:
                    throw new CommandException(__('Unknown or invalid payment status'));
            }
        } catch (ResponseException $e) {
            $this->apiErrorHandler->handleError($e);
        }
    }

    /**
     * @param PaymentDefinition $payment
     * @param Order $order
     * @throws LocalizedException
     */
    private function processOrder(PaymentDefinition $payment, Order $order)
    {
        $paymentId = $payment->id;
        $paymentStatus = $payment->status;
        $paymentStatusCode = $payment->statusOutput->statusCode;

        /** @var Payment $payment */
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(Config::PAYMENT_ID_KEY, $paymentId);
        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_KEY, $paymentStatus);
        $payment->setAdditionalInformation('payment_status', $paymentStatus);
        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_CODE_KEY, $paymentStatusCode);

        $order->addRelatedObject($payment);
        $this->orderRepository->save($order);

        /**
         * Send new Order Email
         */
        try {
            $this->orderSender->send($order);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
