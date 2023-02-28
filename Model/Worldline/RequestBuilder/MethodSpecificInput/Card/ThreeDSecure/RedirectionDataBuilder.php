<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\MethodSpecificInput\Card\ThreeDSecure;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RedirectionData;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RedirectionDataFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Gateway\Command\CreatePaymentRequest\RedirectRequestBuilder;
use Worldline\Connect\Model\ConfigInterface;

class RedirectionDataBuilder
{
    /**
     * @var RedirectionDataFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $redirectionDataFactory;

    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $config;

    /**
     * @var UrlInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $urlBuilder;

    public function __construct(
        RedirectionDataFactory $redirectionDataFactory,
        ConfigInterface $config,
        UrlInterface $urlBuilder
    ) {
        $this->redirectionDataFactory = $redirectionDataFactory;
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    public function create(OrderInterface $order): RedirectionData
    {
        $redirectionData = $this->redirectionDataFactory->create();

        $redirectionData->variant = $this->getHostedCheckoutVariant($order);
        try {
            $redirectionData->returnUrl = $this->urlBuilder->getUrl(
                RedirectRequestBuilder::REDIRECT_PAYMENT_RETURN_URL
            );
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (NotFoundException $exception) {
            // Do nothing
        }

        return $redirectionData;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    private function getHostedCheckoutVariant(OrderInterface $order)
    {
        if ($order->getCustomerIsGuest()) {
            return $this->config->getHostedCheckoutGuestVariant(($order->getStoreId()));
        }
        return $this->config->getHostedCheckoutVariant($order->getStoreId());
    }
}
