<?php

declare(strict_types=1);

namespace Worldline\Connect\Controller\InlinePayment;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Order\OrderServiceInterface;
use Worldline\Connect\Model\Worldline\Action\GetInlinePaymentStatus;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;

use function __;
use function mb_strtolower;

class ProcessReturn extends Action
{
    /**
     * @var Session
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $checkoutSession;

    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $ePaymentsConfig;

    /**
     * @var LoggerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    /**
     * @var GetInlinePaymentStatus
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $inlinePaymentStatus;

    /**
     * @var ClientInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected $worldlineClient;
    private OrderServiceInterface $orderService;

    /**
     * ProcessReturn constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     * @param GetInlinePaymentStatus $getInlinePaymentStatus
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        ConfigInterface $config,
        LoggerInterface $logger,
        GetInlinePaymentStatus $getInlinePaymentStatus,
        ClientInterface $worldlineClient,
        OrderServiceInterface $orderService
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->ePaymentsConfig = $config;
        $this->logger = $logger;
        $this->inlinePaymentStatus = $getInlinePaymentStatus;
        $this->worldlineClient = $worldlineClient;
        $this->orderService = $orderService;
    }

    /**
     * Executes when a customer returns from an inline payment that caused a redirect.
     *
     * @return ResponseInterface|ResultInterface
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function execute()
    {
        $payment = $this->worldlineClient->worldlinePayment($this->retrievePaymentRefId());

        if ($payment->status === StatusInterface::REJECTED) {
            /** @var Order $order */
            $order = $this->orderService->getByIncrementId($payment->paymentOutput->references->merchantReference);

            $this->inlinePaymentStatus->process($order, $payment);

            $order->setState(Order::STATE_PAYMENT_REVIEW);

            /** @var Order\Payment $orderPayment */
            $orderPayment = $order->getPayment();
            $orderPayment->setIsTransactionClosed(true);
            $orderPayment->setIsTransactionDenied(true);
            $orderPayment->update();

            $this->orderService->save($order);

            $message = $this->ePaymentsConfig->getPaymentStatusInfo($payment->status);

            $this->messageManager->addErrorMessage($message);
            $this->logger->error(__($message));
            $this->checkoutSession->restoreQuote();

            return $this->redirect('checkout/cart');
        }

        try {
            $payment = $this->worldlineClient->worldlinePayment($this->retrievePaymentRefId());

            /** @var Order $order */
            $order = $this->orderService->getByIncrementId($payment->paymentOutput->references->merchantReference);

            $this->inlinePaymentStatus->process($order, $payment);

            $this->orderService->save($order);

            /** @var string $worldlinePaymentStatus */
            $worldlinePaymentStatus = $order->getPayment()->getAdditionalInformation(Config::PAYMENT_STATUS_KEY);

            /** @var string $info */
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $info = $this->ePaymentsConfig->getPaymentStatusInfo(mb_strtolower($worldlinePaymentStatus));
            if ($worldlinePaymentStatus === StatusInterface::REJECTED) {
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                throw new LocalizedException($info ? __($info) : __('Unknown status'));
            }

            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $this->messageManager->addSuccessMessage(__('Payment status:') . ' ' . ($info ?: 'Unknown status'));

            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

            $redirectUrl = $order->getPayment()->getAdditionalInformation(Config::REDIRECT_URL_KEY);

            if ($redirectUrl) {
                $order->getPayment()->setAdditionalInformation(Config::REDIRECT_URL_KEY, null);

                $this->orderService->save($order);

                $resultRedirect->setUrl($redirectUrl);

                return $resultRedirect;
            } else {
                $resultRedirect->setPath('checkout/onepage/success');

                $message = $this->ePaymentsConfig->getPaymentStatusInfo(mb_strtolower($worldlinePaymentStatus));
                if ($message) {
                    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName, Squiz.Strings.DoubleQuoteUsage.ContainsVar
                    $this->messageManager->addSuccessMessage(__('Payment status:') . " $message");
                }
                $resultsMessage = $order->getPayment()->getAdditionalInformation(Config::TRANSACTION_RESULTS_KEY);
                if ($resultsMessage) {
                    $this->messageManager->addNoticeMessage($resultsMessage);
                }

                return $this->redirect('checkout/onepage/success');
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->error($e->getMessage());
            $this->checkoutSession->restoreQuote();
            return $this->redirect('checkout/cart');
        }
    }

    /**
     * Return redirect object
     *
     * @param string $path
     * @return ResultInterface
     */
    private function redirect($path)
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($path);

        return $resultRedirect;
    }

    /**
     * @return string
     * @throws NotFoundException
     */
    private function retrievePaymentRefId()
    {
        $paymentRefId = $this->getRequest()->getParam('REF', false);

        if (!$paymentRefId && $this->checkoutSession->getLastRealOrder()->getPayment() !== null) {
            $paymentRefId = $this->checkoutSession
                ->getLastRealOrder()
                ->getPayment()
                ->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        }
        if (!$paymentRefId) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new NotFoundException(__('Could not retrieve payment status.'));
        }

        return $paymentRefId;
    }
}
