<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\MethodSpecificInput\Card;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ThreeDSecure;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ThreeDSecureFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Worldline\Connect\Model\Worldline\RequestBuilder\MethodSpecificInput\Card\ThreeDSecure\RedirectionDataBuilder;

class ThreeDSecureBuilder
{
    public const AUTHENTICATION_FLOW_BROWSER = 'browser';

    /**
     * @var ThreeDSecureFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $threeDSecureFactory;

    /**
     * @var RedirectionDataBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $redirectionDataBuilder;

    public function __construct(
        ThreeDSecureFactory $threeDSecureFactory,
        RedirectionDataBuilder $redirectionDataBuilder
    ) {
        $this->threeDSecureFactory = $threeDSecureFactory;
        $this->redirectionDataBuilder = $redirectionDataBuilder;
    }

    public function create(
        OrderInterface $order
    ): ThreeDSecure {
        $threeDSecure = $this->threeDSecureFactory->create();
        $threeDSecure->redirectionData = $this->redirectionDataBuilder->create($order);

        $threeDSecure->authenticationFlow = self::AUTHENTICATION_FLOW_BROWSER;
        return $threeDSecure;
    }
}
