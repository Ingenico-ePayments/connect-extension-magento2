<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RedirectPaymentMethodSpecificInputFactory;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class RedirectDecorator
 */
class RedirectDecorator implements DecoratorInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RedirectPaymentMethodSpecificInputFactory
     */
    private $redirectTransferPaymentMethodSpecificInputFactory;

    /**
     * RedirectDecorator constructor.
     *
     * @param ConfigInterface $config
     * @param UrlInterface $urlBuilder
     * @param RedirectPaymentMethodSpecificInputFactory $redirectTransferPaymentMethodSpecificInputFactory
     */
    public function __construct(
        ConfigInterface $config,
        UrlInterface $urlBuilder,
        RedirectPaymentMethodSpecificInputFactory $redirectTransferPaymentMethodSpecificInputFactory
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
        $this->redirectTransferPaymentMethodSpecificInputFactory = $redirectTransferPaymentMethodSpecificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->redirectTransferPaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);
        if ($order->getPayment()->getAdditionalInformation(Config::CLIENT_PAYLOAD_KEY)) {
            $input->returnUrl = $this->urlBuilder->getUrl(RequestBuilder::REDIRECT_PAYMENT_RETURN_URL);
        } else {
            $input->returnUrl = $this->urlBuilder->getUrl(RequestBuilder::HOSTED_CHECKOUT_RETURN_URL);
        }

        $tokenize = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY);
        $input->tokenize = ($tokenize === '1');

        $captureMode = $this->config->getCaptureMode($order->getStoreId());
        $input->requiresApproval = ($captureMode === Config::CONFIG_INGENICO_CAPTURES_MODE_AUTHORIZE);

        $request->redirectPaymentMethodSpecificInput = $input;

        return $request;
    }
}
