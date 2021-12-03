<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use DateTime;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\Connect\Model\Ingenico\Action\MerchantAction;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\CreatePayment\RequestBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Payment\ResolverInterface;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Psr\Log\LoggerInterface;

use function array_filter;
use function explode;
use function json_encode;
use function substr;

/**
 * The CreatePayment action is used for orders that have an encrypted client payload
 * that is used to bypass the hosted checkout page.
 *
 * @link https://epayments-api.developer-ingenico.com/s2sapi/v1/en_US/php/payments/create.html
 */
class CreatePayment implements ActionInterface
{
    private const MAP = [
        2 => 'AE',
        146 => 'AU',
        132 => 'DN',
        128 => 'DI',
        163 => 'HC',
        125 => 'JCB',
        117 => 'SM',
        3 => 'MC',
        119 => 'MC',
        1 => 'VI',
        114 => 'VI',
        122 => 'VI',
    ];

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
     * @var PaymentTokenFactory
     */
    private $paymentTokenFactory;
    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * CreatePayment constructor.
     *
     * @param ClientInterface $client
     * @param RequestBuilder $requestBuilder
     * @param ResolverInterface $resolver
     * @param MerchantAction $merchantAction
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
        PaymentTokenFactory $paymentTokenFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        OrderRepositoryInterface $orderRepository,
        Order\Email\Sender\OrderSender $orderSender,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->requestBuilder = $requestBuilder;
        $this->statusResolver = $resolver;
        $this->merchantAction = $merchantAction;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
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
        $customerId = $order->getCustomerId();
        if ($customerId && $response->creationOutput && $response->creationOutput->token) {
            $tokens = array_filter(explode(',', $response->creationOutput->token));
            foreach ($tokens as $token) {
                $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
                    $token,
                    ConfigProvider::CODE,
                    $customerId
                );
                if ($paymentToken !== null) {
                    continue;
                }

                $paymentToken = $this->buildPaymentToken($response, $token);
                if ($paymentToken === null) {
                    continue;
                }

                $orderPayment = $order->getPayment();

                $orderPayment->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, 1);
                $orderPayment->getExtensionAttributes()->setVaultPaymentToken($paymentToken);
            }
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

    /**
     * @param CreatePaymentResponse $response
     * @param $token
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    private function buildPaymentToken(CreatePaymentResponse $response, $token)
    {
        $cardPaymentMethodSpecificOutput = $response->payment->paymentOutput->cardPaymentMethodSpecificOutput;
        if ($cardPaymentMethodSpecificOutput === null) {
            return null;
        }

        $paymentProductId = $cardPaymentMethodSpecificOutput->paymentProductId;
        $card = $cardPaymentMethodSpecificOutput->card;

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setExpiresAt((DateTime::createFromFormat('my', $card->expiryDate))->format('Y-m-1 00:00:00'));
        $paymentToken->setGatewayToken($token);
        $paymentToken->setTokenDetails(json_encode([
            'card' => substr($card->cardNumber, -4),
            'expiry' => (DateTime::createFromFormat('my', $card->expiryDate))->format('m/y'),
            'type' => self::MAP[$paymentProductId] ?: null,
        ]));

        return $paymentToken;
    }
}
