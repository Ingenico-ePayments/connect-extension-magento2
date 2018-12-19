<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificInputFactory;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\AbstractRequestBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class CardDecorator
 */
class CardDecorator implements DecoratorInterface
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
     * @var CardPaymentMethodSpecificInputFactory
     */
    private $cardPaymentMethodSpecificInputFactory;

    /**
     * CardDecorator constructor.
     *
     * @param ConfigInterface $config
     * @param UrlInterface $urlBuilder
     * @param CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory
     */
    public function __construct(
        ConfigInterface $config,
        UrlInterface $urlBuilder,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->cardPaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);
        /** Crude way to detect inline vs hosted checkout. */
        if ($order->getPayment()->getAdditionalInformation(Config::CLIENT_PAYLOAD_KEY)) {
            $input->returnUrl = $this->urlBuilder->getUrl(RequestBuilder::REDIRECT_PAYMENT_RETURN_URL);
        } else {
            $input->returnUrl = $this->urlBuilder->getUrl(RequestBuilder::HOSTED_CHECKOUT_RETURN_URL);
        }

        // Retrieve capture mode from config
        $captureMode = $this->config->getCaptureMode($order->getStoreId());
        $input->requiresApproval = (
            $captureMode === Config::CONFIG_INGENICO_CAPTURES_MODE_AUTHORIZE
        );

        $tokenize = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY);
        $input->tokenize = ($tokenize === '1');

        $input->transactionChannel = 'ECOMMERCE';

        // Skip auth for recurring payments
        if ($input->isRecurring && $input->recurringPaymentSequenceIndicator === 'recurring') {
            $input->skipAuthentication = true;
        } else {
            $input->skipAuthentication = false;
        }

        $request->cardPaymentMethodSpecificInput = $input;

        return $request;
    }
}
