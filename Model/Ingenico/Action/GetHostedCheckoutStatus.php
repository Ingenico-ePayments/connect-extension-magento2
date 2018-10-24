<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Hostedcheckout\GetHostedCheckoutResponse;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;
use Netresearch\Epayments\Model\Ingenico\Token\TokenServiceInterface;
use Netresearch\Epayments\Model\Order\OrderServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Uses to update Magento Order state/status after payment creation via HostedCheckout Payment method.
 *
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__hostedcheckouts__hostedCheckoutId__get
 */
class GetHostedCheckoutStatus implements ActionInterface
{
    const PAYMENT_CREATED = 'PAYMENT_CREATED';
    const IN_PROGRESS = 'IN_PROGRESS';
    const PAYMENT_STATUS_CATEGORY_SUCCESSFUL = 'SUCCESSFUL';
    const PAYMENT_STATUS_CATEGORY_UNKNOWN = 'STATUS_UNKNOWN';
    const PAYMENT_STATUS_CATEGORY_REJECTED = 'REJECTED';
    const PAYMENT_OUTPUT_SHOW_INSTRUCTIONS = 'SHOW_INSTRUCTIONS';

    /** @var LoggerInterface */
    private $logger;

    /** @var ClientInterface */
    private $client;

    /** @var ConfigInterface */
    private $ePaymentsConfig;

    /** @var \Magento\Framework\App\Request\Http */
    private $request;

    /** @var OrderSender */
    private $orderSender;

    /** @var ResolverInterface */
    private $statusResolver;

    /** @var TokenServiceInterface */
    private $tokenService;

    /** @var OrderRepository */
    private $orderRepository;

    /**
     * @var OrderServiceInterface
     */
    private $orderService;

    /**
     * GetHostedCheckoutStatus constructor.
     *
     * @param LoggerInterface $logger
     * @param ClientInterface $client
     * @param ConfigInterface $ePaymentsConfig
     * @param Http $request
     * @param OrderSender $orderSender
     * @param ResolverInterface $statusResolver
     * @param TokenServiceInterface $tokenService
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
        TokenServiceInterface $tokenService,
        OrderRepository $orderRepository,
        OrderServiceInterface $orderService
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->request = $request;
        $this->orderSender = $orderSender;
        $this->statusResolver = $statusResolver;
        $this->tokenService = $tokenService;
        $this->orderRepository = $orderRepository;
        $this->orderService = $orderService;
    }

    /**
     * Load HostedCheckout instance from API and apply it to corresponding order
     *
     * @param $hostedCheckoutId
     * @return OrderInterface|null
     * @throws LocalizedException
     */
    public function process($hostedCheckoutId)
    {
        $statusResponse = $this->getStatusResponse($hostedCheckoutId);

        $this->validateResponse($statusResponse);
        $incrementId = $statusResponse->createdPaymentOutput->payment->paymentOutput->references->merchantReference;
        $order = $this->orderService->getByIncrementId($incrementId);

        $this->checkPaymentStatusCategory($statusResponse, $order);

        if ($statusResponse->status === self::PAYMENT_CREATED) {
            $this->checkReturnmac($order);
            $this->processOrder($order, $statusResponse);
            $this->processTokens($order, $statusResponse);
            try {
                $this->orderSender->send($order);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $this->orderRepository->save($order);

        return $order;
    }

    /**
     * @param OrderInterface $order
     * @param GetHostedCheckoutResponse $statusResponse
     */
    private function processTokens($order, $statusResponse)
    {
        $tokens = $statusResponse->createdPaymentOutput->tokens;
        if ($tokens) {

            /** @var int $customerId */
            $customerId = $order->getCustomerId();
            /** @var string $paymentProductId */
            $paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);
            if (($customerId < 1) || empty($paymentProductId)) {
                $this->logger->error(
                    "Empty value detected: customerId = $customerId, paymentProductId = $paymentProductId"
                );
            } else {
                $this->tokenService->add(
                    $customerId,
                    $paymentProductId,
                    $tokens
                );
            }
        }
    }

    /**
     * Get status response
     *
     * @param string $hostedCheckoutId
     * @return GetHostedCheckoutResponse
     */
    private function getStatusResponse($hostedCheckoutId)
    {
        /** \Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutResponse $statusResponse */
        $statusResponse = $this->client->getIngenicoClient()
                                       ->merchant($this->ePaymentsConfig->getMerchantId())
                                       ->hostedcheckouts()
                                       ->get($hostedCheckoutId);

        return $statusResponse;
    }

    /**
     * Process order
     *
     * @param OrderInterface $order
     * @param GetHostedCheckoutResponse $statusResponse
     * @throws LocalizedException
     */
    private function processOrder(
        OrderInterface $order,
        GetHostedCheckoutResponse $statusResponse
    ) {
        $ingenicoPaymentId = $statusResponse->createdPaymentOutput->payment->id;
        $ingenicoPaymentStatus = $statusResponse->createdPaymentOutput->payment->status;
        $ingenicoPaymentStatusCode = $statusResponse->createdPaymentOutput->payment->statusOutput->statusCode;

        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        if (isset($statusResponse->createdPaymentOutput->displayedData)
            && $statusResponse->createdPaymentOutput->displayedData->displayedDataType
               == self::PAYMENT_OUTPUT_SHOW_INSTRUCTIONS
        ) {
            $payment->setAdditionalInformation(
                Config::PAYMENT_SHOW_DATA_KEY,
                $statusResponse->createdPaymentOutput->displayedData->toJson()
            );
        }

        $this->statusResolver->resolve($order, $statusResponse->createdPaymentOutput->payment);

        $payment->setAdditionalInformation(Config::PAYMENT_ID_KEY, $ingenicoPaymentId);
        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_KEY, $ingenicoPaymentStatus);
        $payment->setAdditionalInformation(Config::PAYMENT_STATUS_CODE_KEY, $ingenicoPaymentStatusCode);

        $info = $this->ePaymentsConfig->getPaymentStatusInfo($ingenicoPaymentStatus);
        if ($info) {
            $order->addStatusHistoryComment(
                sprintf(
                    "%s (payment status: '%s', payment status code: '%s')",
                    $info,
                    $ingenicoPaymentStatus,
                    $ingenicoPaymentStatusCode
                )
            );
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
        $ingenicoReturnmac = $this->request->get('RETURNMAC');
        if ($ingenicoReturnmac === null) {
            return;
        }
        $orderReturnmac = $order->getPayment()->getAdditionalInformation('ingenico_returnmac');
        if ($ingenicoReturnmac != $orderReturnmac) {
            throw new LocalizedException(__('RETURNMAC doesn\'t match.'));
        }
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
            $order->addStatusHistoryComment("<b>Payment error, status</b><br />{$status} : $msg");
            $this->orderRepository->save($order);
            throw new LocalizedException(__($msg));
        }
    }

    /**
     * @param GetHostedCheckoutResponse $statusResponse
     * @throws LocalizedException
     */
    private function validateResponse(GetHostedCheckoutResponse $statusResponse)
    {
        if (!$statusResponse->createdPaymentOutput) {
            $msg = __('Your payment was rejected or a technical error occured during processing.');
            throw new LocalizedException(__($msg));
        }
    }
}
