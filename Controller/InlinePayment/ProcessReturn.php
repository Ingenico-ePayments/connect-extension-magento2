<?php

namespace Netresearch\Epayments\Controller\InlinePayment;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Cart\ServiceInterface;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Action\RetrievePayment;
use Psr\Log\LoggerInterface;

class ProcessReturn extends Action
{
    /** @var Session */
    private $checkoutSession;

    /** @var RetrievePayment */
    private $retrievePayment;

    /** @var ConfigInterface */
    private $ePaymentsConfig;

    /** @var LoggerInterface */
    private $logger;

    /** @var ServiceInterface */
    private $refillCartService;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param RetrievePayment $retrievePayment
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        RetrievePayment $retrievePayment,
        ConfigInterface $config,
        LoggerInterface $logger,
        ServiceInterface $refillCartService
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->retrievePayment = $retrievePayment;
        $this->ePaymentsConfig = $config;
        $this->logger = $logger;
        $this->refillCartService = $refillCartService;
    }

    /**
     * Executes when a customer returns from a redirect payment
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var Order $order */
        $order = $this->checkoutSession->getLastRealOrder();
        $orderId = (int)$order->getRealOrderId();

        if ($orderId < 1) {
            return $this->redirect('checkout/cart');
        }

        try {
            /** @TODO(nr): This will currently not report if the order has been canceled on the redirect page. */
            $this->retrievePayment->process($order);
            /** @var string $ingenicoPaymentStatus */
            $ingenicoPaymentStatus = $order->getPayment()->getAdditionalInformation(Config::PAYMENT_STATUS_KEY);
            /** @var string $info */
            $info = $this->ePaymentsConfig->getPaymentStatusInfo(mb_strtolower($ingenicoPaymentStatus));
            if ($info) {
                $this->messageManager->addSuccessMessage(__('Payment status:') . " $info");
            }

            return $this->redirect('checkout/onepage/success');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->error($e->getMessage());
        }

        // repopulate cart with products
        if ($order->isCanceled()) {
            $errorsOccured = $this->refillCartService->fillCartFromOrder($this->checkoutSession, $order);
            if ($errorsOccured) {
                foreach ($this->refillCartService->getErrors() as $errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage, 'refill_cart');
                    $this->logger->error($errorMessage);
                }
            }
        }

        return $this->redirect('checkout/cart');
    }

    /**
     * Return redirect object
     *
     * @param $path
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function redirect($path)
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($path);

        return $resultRedirect;
    }
}
