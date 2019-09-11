<?php

namespace Ingenico\Connect\Controller\HostedCheckoutPage;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\Config;

class Index extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Get redirect url from last order and initiates redirect process
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var Order $order */
        $order = $this->checkoutSession->getLastRealOrder();

        /** @var InfoInterface $payment */
        $payment = $order->getPayment();
        $ingenicoRedirectUrl = $payment->getAdditionalInformation(Config::REDIRECT_URL_KEY);

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($ingenicoRedirectUrl);

        return $resultRedirect;
    }
}
