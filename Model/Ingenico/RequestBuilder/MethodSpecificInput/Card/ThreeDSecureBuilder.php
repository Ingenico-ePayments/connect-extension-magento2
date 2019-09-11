<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card\ThreeDSecure\RedirectionDataBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ThreeDSecure;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ThreeDSecureFactory;
use Magento\Sales\Api\Data\OrderInterface;

class ThreeDSecureBuilder
{
    const AUTHENTICATION_FLOW_BROWSER = 'browser';

    /**
     * @var ThreeDSecureFactory
     */
    private $threeDSecureFactory;

    /**
     * @var RedirectionDataBuilder
     */
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
