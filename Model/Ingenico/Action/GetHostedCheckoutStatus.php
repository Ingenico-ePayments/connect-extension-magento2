<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Ingenico\Connect\Helper\Token as TokenHelper;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\Connect\Model\Ingenico\Action\HostedCheckout\StatusManagement;
use Ingenico\Connect\Model\Ingenico\Status\Payment\ResolverInterface;
use Ingenico\Connect\Model\Order\OrderServiceInterface;
use Ingenico\Connect\Model\StatusResponseManagerInterface;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\GetHostedCheckoutResponse;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Psr\Log\LoggerInterface;

use function array_filter;
use function explode;

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
    const CANCELLED_BY_CONSUMER = 'CANCELLED_BY_CONSUMER';

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @var StatusResponseManagerInterface
     */
    private $statusResponseManager;

    /**
     * @var StatusManagement
     */
    private $statusManagement;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var TokenHelper
     */
    private $tokenHelper;

    public function __construct(
        LoggerInterface $logger,
        ConfigInterface $ePaymentsConfig,
        Http $request,
        OrderSender $orderSender,
        ResolverInterface $statusResolver,
        OrderRepository $orderRepository,
        OrderServiceInterface $orderService,
        StatusResponseManagerInterface $statusResponseManager,
        StatusManagement $statusManagement,
        PaymentTokenManagementInterface $paymentTokenManagement,
        TokenHelper $tokenHelper
    ) {
        $this->logger = $logger;
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->request = $request;
        $this->orderSender = $orderSender;
        $this->statusResolver = $statusResolver;
        $this->orderRepository = $orderRepository;
        $this->orderService = $orderService;
        $this->statusResponseManager = $statusResponseManager;
        $this->statusManagement = $statusManagement;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->tokenHelper = $tokenHelper;
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
        $statusResponse = $this->statusManagement->getStatus($hostedCheckoutId);

        if ($statusResponse->status === self::CANCELLED_BY_CONSUMER) {
            $order->cancel();
        } else {
            $this->validateResponse($statusResponse);
            $this->checkPaymentStatusCategory($statusResponse, $order);

            if ($statusResponse->status === self::PAYMENT_CREATED) {
                $this->checkReturnmac($order);
                $this->processOrder($order, $statusResponse);
                try {
                    $this->orderSender->send($order);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
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

        $this->processToken($statusResponse, $order);
    }

    /**
     * @param string $hostedCheckoutId
     * @return OrderInterface
     * @throws LocalizedException
     */
    private function getOrder(string $hostedCheckoutId): OrderInterface
    {
        try {
            $order = $this->orderService->getByHostedCheckoutId($hostedCheckoutId);
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(
                __('There was no order found for RPP (hosted checkout ID: %1)', $hostedCheckoutId)
            );
        }

        return $order;
    }

    /**
     * @param GetHostedCheckoutResponse $statusResponse
     * @param OrderInterface $order
     */
    private function processToken(GetHostedCheckoutResponse $statusResponse, OrderInterface $order): void
    {
        $paymentOutput = $statusResponse->createdPaymentOutput->payment->paymentOutput;
        if ($paymentOutput === null) {
            return;
        }

        $cardPaymentMethodSpecificOutput = $paymentOutput->cardPaymentMethodSpecificOutput;
        if ($cardPaymentMethodSpecificOutput === null) {
            return;
        }

        $customerId = $order->getCustomerId();
        if ($customerId && $statusResponse->createdPaymentOutput->tokens) {
            $tokens = array_filter(explode(',', $statusResponse->createdPaymentOutput->tokens));
            foreach ($tokens as $token) {
                $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
                    $token,
                    ConfigProvider::CODE,
                    $customerId
                );
                if ($paymentToken !== null) {
                    continue;
                }

                $orderPayment = $order->getPayment();
                $orderPayment->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, 1);
                $orderPayment->getExtensionAttributes()->setVaultPaymentToken($this->tokenHelper->buildPaymentToken(
                    $cardPaymentMethodSpecificOutput,
                    $token
                ));
            }
        }
    }
}
