<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Controller\InlinePayment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Model\InfoInterface;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;

class Index extends Action
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

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Add payment status message and redirect to success page.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function execute()
    {
        /** @var InfoInterface $payment */
        $payment = $this->checkoutSession->getLastRealOrder()->getPayment();

        $worldlinePaymentStatus = $payment->getAdditionalInformation(Config::PAYMENT_STATUS_KEY);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $message = $this->config->getPaymentStatusInfo(mb_strtolower($worldlinePaymentStatus));
        $resultsMessage = $payment->getAdditionalInformation(Config::TRANSACTION_RESULTS_KEY);

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirectUrl = $payment->getAdditionalInformation(Config::REDIRECT_URL_KEY);

        if ($redirectUrl) {
            $resultRedirect->setUrl($redirectUrl);
        } else {
            $resultRedirect->setPath('checkout/onepage/success');

            if ($message) {
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName, Squiz.Strings.DoubleQuoteUsage.ContainsVar
                $this->messageManager->addSuccessMessage(__('Payment status:') . " $message");
            }
            if ($resultsMessage) {
                $this->messageManager->addNoticeMessage($resultsMessage);
            }
        }

        return $resultRedirect;
    }
}
