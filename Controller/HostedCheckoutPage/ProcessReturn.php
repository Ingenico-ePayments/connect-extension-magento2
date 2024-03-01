<?php

declare(strict_types=1);

namespace Worldline\Connect\Controller\HostedCheckoutPage;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Worldline\Action\GetHostedCheckoutStatus;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;

class ProcessReturn extends Action
{
    /**
     * @var SessionManagerInterface|Session
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $checkoutSession;

    /**
     * @var GetHostedCheckoutStatus
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $checkoutStatus;

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
    private ClientInterface $client;

    /**
     * ProcessReturn constructor.
     *
     * @param Context $context
     * @param SessionManagerInterface $checkoutSession
     * @param GetHostedCheckoutStatus $checkoutStatus
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $checkoutSession,
        GetHostedCheckoutStatus $checkoutStatus,
        ConfigInterface $config,
        LoggerInterface $logger,
        ClientInterface $client,
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->checkoutStatus = $checkoutStatus;
        $this->ePaymentsConfig = $config;
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * Executes when a customer returns from Hosted Checkout
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();
            $getHostedCheckoutResponse = $this->client->getHostedCheckout($this->retrieveHostedCheckoutId($order));

            $this->checkoutStatus->process($order, $getHostedCheckoutResponse);

            // Handle order cancellation:
            if ($order->getState() === Order::STATE_CANCELED) {
                $this->messageManager->addNoticeMessage(
                    // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                    __('You cancelled the payment. Please select a different payment option and place your order again')
                );
                $this->checkoutSession->restoreQuote();
                return $this->redirect('checkout/cart');
            }

            return $this->redirect('checkout/onepage/success');
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
     * @param $url
     * @return ResultInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
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
    private function retrieveHostedCheckoutId(Order $order)
    {
        $hostedCheckoutId = $this->getRequest()->getParam('hostedCheckoutId', false);
        if ($hostedCheckoutId === false && $this->checkoutSession->getLastRealOrder()->getPayment() !== null) {
            $hostedCheckoutId = $order->getPayment()->getAdditionalInformation(Config::HOSTED_CHECKOUT_ID_KEY);
        }

        // $hostedCheckoutId can be false or null in error case
        if (!$hostedCheckoutId) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new NotFoundException(__('Could not retrieve payment status.'));
        }

        return $hostedCheckoutId;
    }
}
