<?php

declare(strict_types=1);

namespace Worldline\Connect\Controller\InlinePayment;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Worldline\Action\GetInlinePaymentStatus;
use Worldline\Connect\Model\Worldline\StatusInterface;

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

            /** @var string $worldlinePaymentStatus */
            $worldlinePaymentStatus = $order->getPayment()->getAdditionalInformation(Config::PAYMENT_STATUS_KEY);

            exit;

            /** @var string $info */
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $info = $this->ePaymentsConfig->getPaymentStatusInfo(mb_strtolower($worldlinePaymentStatus));
            if ($worldlinePaymentStatus === StatusInterface::REJECTED) {
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                throw new LocalizedException($info ? __($info) : __('Unknown status'));
            }
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new NotFoundException(__('Could not retrieve payment status.'));
        }

        return $paymentRefId;
    }
}
