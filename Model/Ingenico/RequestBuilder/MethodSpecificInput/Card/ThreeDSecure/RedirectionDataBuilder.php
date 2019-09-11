<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card\ThreeDSecure;

use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RedirectionData;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RedirectionDataFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class RedirectionDataBuilder
{
    /**
     * @var RedirectionDataFactory
     */
    private $redirectionDataFactory;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var UrlInterface
     */
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

        $redirectionData->variant = $this->config->getHostedCheckoutVariant($order->getStoreId());
        try {
            $redirectionData->returnUrl = $this->getReturnUrl($order);
        } catch (NotFoundException $exception) {
            // Do nothing
        }

        return $redirectionData;
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws NotFoundException
     */
    private function getReturnUrl(OrderInterface $order): string
    {
        if (!$order->getPayment() instanceof OrderPaymentInterface) {
            throw new NotFoundException(__('No payment found'));
        }
        if ($order->getPayment()->getAdditionalInformation(Config::CLIENT_PAYLOAD_KEY)) {
            return $this->urlBuilder->getUrl(RequestBuilder::REDIRECT_PAYMENT_RETURN_URL);
        }
        return $this->urlBuilder->getUrl(RequestBuilder::HOSTED_CHECKOUT_RETURN_URL);
    }
}
