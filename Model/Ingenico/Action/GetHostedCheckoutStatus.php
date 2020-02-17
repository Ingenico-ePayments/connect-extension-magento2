<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Ingenico\Connect\Model\Ingenico\Action\HostedCheckout\TokenManagement;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\GetHostedCheckoutResponse;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\MerchantReference;
use Ingenico\Connect\Model\Ingenico\Status\ResolverInterface;
use Ingenico\Connect\Model\Order\OrderServiceInterface;
use Ingenico\Connect\Model\StatusResponseManagerInterface;
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

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var ConfigInterface
     */
    private $ePaymentsConfig;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderServiceInterface
     */
    private $orderService;

    /**
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * @var StatusResponseManagerInterface
     */
    private $statusResponseManager;

    /**
     * @var TokenManagement
     */
    private $tokenManagement;

    public function __construct(
        LoggerInterface $logger,
        ClientInterface $client,
        ConfigInterface $ePaymentsConfig,
        Http $request,
        OrderSender $orderSender,
        ResolverInterface $statusResolver,
        OrderRepository $orderRepository,
        OrderServiceInterface $orderService,
        MerchantReference $merchantReference,
        StatusResponseManagerInterface $statusResponseManager,
        TokenManagement $tokenManagement
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->request = $request;
        $this->orderSender = $orderSender;
        $this->statusResolver = $statusResolver;
        $this->orderRepository = $orderRepository;
        $this->orderService = $orderService;
        $this->merchantReference = $merchantReference;
        $this->statusResponseManager = $statusResponseManager;
        $this->tokenManagement = $tokenManagement;
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
        $incrementId = $this->merchantReference->extractOrderReference(
            $statusResponse->createdPaymentOutput->payment->paymentOutput->references->merchantReference
        );
        $order = $this->orderService->getByIncrementId($incrementId);

        $this->checkPaymentStatusCategory($statusResponse, $order);

        if ($statusResponse->status === self::PAYMENT_CREATED) {
            $this->checkReturnmac($order);
            $this->processOrder($order, $statusResponse);
            $this->tokenManagement->processTokens($order, $statusResponse);
            try {
                $this->orderSender->send($order);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        try {
            $this->orderRepository->save($order);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $order;
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
    }
}
