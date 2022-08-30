<?php

namespace Ingenico\Connect\PaymentMethod;

use Ingenico\Connect\Gateway\Command\ApiErrorHandler;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\Action\ApprovePayment;
use Ingenico\Connect\Model\Ingenico\Action\CapturePayment;
use Ingenico\Connect\Model\Ingenico\Action\MerchantAction;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\FraudFieldsBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\MerchantBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\OrderBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Payment\ResolverInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Sdk\Domain\Definitions\FraudFields;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequestFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as PaymentDefinition;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RedirectPaymentMethodSpecificInputFactory;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;
use function array_search;
use function is_int;

class Redirect implements CommandInterface
{
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
     * @var RedirectPaymentMethodSpecificInputFactory
     */
    private $redirectTransferPaymentMethodSpecificInputFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

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
     * @param CreatePaymentRequestFactory $createPaymentRequestFactory
     * @param OrderBuilder $orderBuilder
     * @param MerchantBuilder $merchantBuilder
     * @param FraudFieldsBuilder $fraudFieldsBuilder
     * @param RedirectPaymentMethodSpecificInputFactory $redirectTransferPaymentMethodSpecificInputFactory
     * @param UrlInterface $urlBuilder
     * @param CapturePayment $capturePayment
     * @param ApprovePayment $approvePayment
     * @param ApiErrorHandler $apiErrorHandler
     * @param ClientInterface $client
     * @param MerchantAction $merchantAction
     * @param OrderRepositoryInterface $orderRepository
     * @param Order\Email\Sender\OrderSender $orderSender
     * @param LoggerInterface $logger
     * @param ResolverInterface $statusResolver
     */
    public function __construct(
        CreatePaymentRequestFactory $createPaymentRequestFactory,
        OrderBuilder $orderBuilder,
        MerchantBuilder $merchantBuilder,
        FraudFieldsBuilder $fraudFieldsBuilder,
        RedirectPaymentMethodSpecificInputFactory $redirectTransferPaymentMethodSpecificInputFactory,
        UrlInterface $urlBuilder,
        CapturePayment $capturePayment,
        ApprovePayment $approvePayment,
        ApiErrorHandler $apiErrorHandler,
        ClientInterface $client,
        MerchantAction $merchantAction,
        OrderRepositoryInterface $orderRepository,
        Order\Email\Sender\OrderSender $orderSender,
        LoggerInterface $logger,
        ResolverInterface $statusResolver
    ) {
        $this->createPaymentRequestFactory = $createPaymentRequestFactory;
        $this->orderBuilder = $orderBuilder;
        $this->merchantBuilder = $merchantBuilder;
        $this->fraudFieldsBuilder = $fraudFieldsBuilder;
        $this->redirectTransferPaymentMethodSpecificInputFactory = $redirectTransferPaymentMethodSpecificInputFactory;
        $this->urlBuilder = $urlBuilder;
        $this->capturePayment = $capturePayment;
        $this->approvePayment = $approvePayment;
        $this->apiErrorHandler = $apiErrorHandler;
        $this->client = $client;
        $this->merchantAction = $merchantAction;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->statusResolver = $statusResolver;
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
        $paymentProductId = PaymentMethods::MAP[$order->getPayment()->getMethod()];
        if ($paymentProductId === false) {
            throw new LocalizedException(__('Unknown payment method.'));
        }

        $request = $this->createPaymentRequestFactory->create();
        $request->order = $this->orderBuilder->create($order);
        $request->merchant = $this->merchantBuilder->create($order);
        $request->fraudFields = $this->fraudFieldsBuilder->create($order);

        $input = $this->redirectTransferPaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $paymentProductId;

        $payload = $order->getPayment()->getAdditionalInformation('input');

        if ($payload) {
            $input->returnUrl = $this->urlBuilder->getUrl(RequestBuilder::REDIRECT_PAYMENT_RETURN_URL);
        } else {
            $input->returnUrl = $this->urlBuilder->getUrl(RequestBuilder::HOSTED_CHECKOUT_RETURN_URL);
        }

        $input->tokenize = false;
        $method = $payment->getMethodInstance();
        $input->requiresApproval = $method->getConfigPaymentAction() === MethodInterface::ACTION_AUTHORIZE;

        $request->redirectPaymentMethodSpecificInput = $input;

        $request->encryptedCustomerInput = $payload;
        $request->fraudFields = new FraudFields();
        $request->fraudFields->customerIpAddress = $order->getRemoteIp();

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
        $status = $payment->getAdditionalInformation('status');

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
    private function processOrder(PaymentDefinition $payment, Order $order): void
    {
        $paymentId = $payment->id;
        $paymentStatus = $payment->status;
        $paymentStatusCode = $payment->statusOutput->statusCode;

        /** @var Payment $payment */
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(Config::PAYMENT_ID_KEY, $paymentId);
        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_KEY, $paymentStatus);
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
