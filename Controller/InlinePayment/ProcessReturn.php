<?php

declare(strict_types=1);

namespace Ingenico\Connect\Controller\InlinePayment;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Action\GetInlinePaymentStatus;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Psr\Log\LoggerInterface;

class ProcessReturn extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var ConfigInterface
     */
    private $ePaymentsConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetInlinePaymentStatus
     */
    private $inlinePaymentStatus;

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
        GetInlinePaymentStatus $getInlinePaymentStatus
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->ePaymentsConfig = $config;
        $this->logger = $logger;
        $this->inlinePaymentStatus = $getInlinePaymentStatus;
    }

    /**
     * Executes when a customer returns from an inline payment that caused a redirect.
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            $paymentRefId = $this->retrievePaymentRefId();
            $order = $this->inlinePaymentStatus->process($paymentRefId);
            /** @var string $ingenicoPaymentStatus */
            $ingenicoPaymentStatus = $order->getPayment()->getAdditionalInformation(Config::PAYMENT_STATUS_KEY);

            /** @var string $info */
            $info = $this->ePaymentsConfig->getPaymentStatusInfo(mb_strtolower($ingenicoPaymentStatus));
            if ($ingenicoPaymentStatus === StatusInterface::REJECTED) {
                throw new LocalizedException($info ? __($info) : __('Unknown status'));
            }
            $this->messageManager->addSuccessMessage(__('Payment status:') . ' ' . ($info ?: 'Unknown status'));

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
            throw new NotFoundException(__('Could not retrieve payment status.'));
        }

        return $paymentRefId;
    }
}
