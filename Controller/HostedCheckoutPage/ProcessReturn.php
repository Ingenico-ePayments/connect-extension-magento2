<?php

namespace Ingenico\Connect\Controller\HostedCheckoutPage;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\Cart\ServiceInterface;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Action\GetHostedCheckoutStatus;
use Psr\Log\LoggerInterface;

class ProcessReturn extends Action
{
    /** @var SessionManagerInterface|Session */
    private $checkoutSession;

    /** @var GetHostedCheckoutStatus */
    private $checkoutStatus;

    /** @var ConfigInterface */
    private $ePaymentsConfig;

    /** @var LoggerInterface */
    private $logger;

    /** @var ServiceInterface */
    private $refillCartService;

    /**
     * ProcessReturn constructor.
     *
     * @param Context $context
     * @param SessionManagerInterface $checkoutSession
     * @param GetHostedCheckoutStatus $checkoutStatus
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     * @param ServiceInterface $refillCartService
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $checkoutSession,
        GetHostedCheckoutStatus $checkoutStatus,
        ConfigInterface $config,
        LoggerInterface $logger,
        ServiceInterface $refillCartService
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->checkoutStatus = $checkoutStatus;
        $this->ePaymentsConfig = $config;
        $this->logger = $logger;
        $this->refillCartService = $refillCartService;
    }

    /**
     * Executes when a customer returns from Hosted Checkout
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var Order $order */
        try {
            $hostedCheckoutId = $this->retrieveHostedCheckoutId();
            $order = $this->checkoutStatus->process($hostedCheckoutId);
            /** @var string $transactionStatus */
            $transactionStatus = $order->getPayment()->getAdditionalInformation(Config::PAYMENT_STATUS_KEY);
            /** @var string $info */
            $info = $this->ePaymentsConfig->getPaymentStatusInfo(mb_strtolower($transactionStatus));
            $this->messageManager->addSuccessMessage(__('Payment status:') . ' ' . ($info ?: 'Unknown status'));

            return $this->redirect('checkout/onepage/success');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->error($e->getMessage());
            $this->refillCart();

            return $this->redirect('checkout/cart');
        }
    }

    /**
     * Return redirect object
     *
     * @param $url
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function redirect($url)
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($url);

        return $resultRedirect;
    }

    /**
     * Load hosted checkout id from request param or fall back to session
     *
     * @return string
     * @throws NotFoundException
     */
    private function retrieveHostedCheckoutId()
    {
        $hostedCheckoutId = $this->getRequest()->getParam('hostedCheckoutId', false);

        if ($hostedCheckoutId === false && $this->checkoutSession->getLastRealOrder()->getPayment() !== null) {
            $hostedCheckoutId = $this->checkoutSession
                ->getLastRealOrder()
                ->getPayment()
                ->getAdditionalInformation(Config::HOSTED_CHECKOUT_ID_KEY);
        }

        // $hostedCheckoutId can be false or null in error case
        if (!$hostedCheckoutId) {
            throw new NotFoundException(__('Could not retrieve payment status.'));
        }

        return $hostedCheckoutId;
    }

    /**
     * @param Order $order
     */
    private function repopuplateCart(Order $order)
    {
        $errorsOccured = $this->refillCartService->fillCartFromOrder($this->checkoutSession, $order);
        if ($errorsOccured) {
            foreach ($this->refillCartService->getErrors() as $errorMessage) {
                $this->messageManager->addErrorMessage($errorMessage, 'refill_cart');
                $this->logger->error($errorMessage);
            }
        }
    }

    /**
     * Refill cart
     */
    private function refillCart()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (isset($order)) {
            $this->repopuplateCart($order);
        }
    }
}
