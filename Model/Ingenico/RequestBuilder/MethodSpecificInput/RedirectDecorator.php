<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RedirectPaymentMethodSpecificInputFactory;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\AbstractRequestBuilder;

/**
 * Class RedirectDecorator
 */
class RedirectDecorator extends AbstractMethodDecorator
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var RedirectPaymentMethodSpecificInputFactory
     */
    private $redirectTransferPaymentMethodSpecificInputFactory;

    /**
     * RedirectDecorator constructor.
     *
     * @param ConfigInterface $config
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param RedirectPaymentMethodSpecificInputFactory $redirectTransferPaymentMethodSpecificInputFactory
     */
    public function __construct(
        ConfigInterface $config,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        RedirectPaymentMethodSpecificInputFactory $redirectTransferPaymentMethodSpecificInputFactory
    ) {
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->redirectTransferPaymentMethodSpecificInputFactory = $redirectTransferPaymentMethodSpecificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->redirectTransferPaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $this->getProductId($order);
        $input->returnUrl = $this->urlBuilder->getUrl(AbstractRequestBuilder::REDIRECT_PAYMENT_RETURN_URL);

        $tokenize = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY);
        $input->tokenize = ($tokenize === '1');

        $captureMode = $this->config->getCaptureMode($this->storeManager->getStore()->getId());
        $input->requiresApproval = ($captureMode === Config::CONFIG_INGENICO_CAPTURES_MODE_AUTHORIZE);

        $request->redirectPaymentMethodSpecificInput = $input;

        return $request;
    }
}
