<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\CreatePayment\RequestBuilder;
use Ingenico\Connect\Model\Ingenico\Status\ResolverInterface;
use Ingenico\Connect\Model\Ingenico\Token\TokenServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * The CreatePayment action is used for orders that have an encrypted client payload
 * that is used to bypass the hosted checkout page.
 *
 * @link https://epayments-api.developer-ingenico.com/s2sapi/v1/en_US/php/payments/create.html
 */
class CreatePayment implements ActionInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var MerchantAction
     */
    private $merchantAction;

    /**
     * @var TokenServiceInterface
     */
    private $tokenService;

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
     * CreatePayment constructor.
     *
     * @param ClientInterface $client
     * @param RequestBuilder $requestBuilder
     * @param ResolverInterface $resolver
     * @param MerchantAction $merchantAction
     * @param TokenServiceInterface $tokenService
     * @param ConfigInterface $config
     * @param OrderRepositoryInterface $orderRepository
     * @param Order\Email\Sender\OrderSender $orderSender
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientInterface $client,
        RequestBuilder $requestBuilder,
        ResolverInterface $resolver,
        MerchantAction $merchantAction,
        TokenServiceInterface $tokenService,
        OrderRepositoryInterface $orderRepository,
        Order\Email\Sender\OrderSender $orderSender,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->requestBuilder = $requestBuilder;
        $this->statusResolver = $resolver;
        $this->merchantAction = $merchantAction;
        $this->tokenService = $tokenService;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
    }

    /**
     * @param Order $order
     * @throws LocalizedException
     */
    public function create(Order $order)
    {
        $request = $this->requestBuilder->create($order);
        try {
            $response = $this->client->createPayment($request);
        } catch (\Ingenico\Connect\Sdk\ResponseException $e) {
            throw new LocalizedException(
                __('There was an error processing your order. Please contact us or try again later.')
            );
        }

        $paymentResponse = $response->payment;

        $this->processToken($order, $response);

        if ($response->merchantAction && $response->merchantAction->actionType) {
            $this->merchantAction->handle($order, $response->merchantAction);
        }

        $this->statusResolver->resolve($order, $paymentResponse);

        $this->handleSuccessfulPayment($order, $response);
    }

    /**
     * @param Order $order
     * @param CreatePaymentResponse $response
     */
    private function processToken(
        Order $order,
        CreatePaymentResponse $response
    ) {
        if ($order->getCustomerId() && $response->creationOutput && $response->creationOutput->token) {
            $tokenString = $response->creationOutput->token;
            $productId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);
            $this->tokenService->add($order->getCustomerId(), $productId, $tokenString);
        }
    }

    /**
     * @param Order $order
     * @param CreatePaymentResponse $statusResponse
     */
    private function handleSuccessfulPayment(
        Order $order,
        CreatePaymentResponse $statusResponse
    ) {
        $paymentId = $statusResponse->payment->id;
        $paymentStatus = $statusResponse->payment->status;
        $paymentStatusCode = $statusResponse->payment->statusOutput->statusCode;

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
