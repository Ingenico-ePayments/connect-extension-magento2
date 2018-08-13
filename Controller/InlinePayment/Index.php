<?php

namespace Netresearch\Epayments\Controller\InlinePayment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Model\InfoInterface;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;

class Index extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param ConfigInterface $config
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        ConfigInterface $config
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * Add payment status message and redirect to success page.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var InfoInterface $payment */
        $payment = $this->checkoutSession->getLastRealOrder()->getPayment();

        $ingenicoPaymentStatus = $payment->getAdditionalInformation(Config::PAYMENT_STATUS_KEY);
        $message               = $this->config->getPaymentStatusInfo(mb_strtolower($ingenicoPaymentStatus));
        $resultsMessage        = $payment->getAdditionalInformation(Config::TRANSACTION_RESULTS_KEY);

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirectUrl    = $payment->getAdditionalInformation(Config::REDIRECT_URL_KEY);

        if ($redirectUrl) {
            $resultRedirect->setUrl($redirectUrl);
        } else {
            $resultRedirect->setPath('checkout/onepage/success');

            if ($message) {
                $this->messageManager->addSuccessMessage(__('Payment status:') . " $message");
            }
            if ($resultsMessage) {
                $this->messageManager->addNoticeMessage($resultsMessage);
            }
        }

        return $resultRedirect;
    }
}
