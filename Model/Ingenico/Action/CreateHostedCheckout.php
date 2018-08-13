<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Errors\ErrorResponse;
use Ingenico\Connect\Sdk\Domain\Product\PaymentProductResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Helper\Data as DataHelper;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\CreateHostedCheckout\RequestBuilder;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#hostedcheckouts
 */
class CreateHostedCheckout extends AbstractAction implements ActionInterface
{
    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * CreateHostedCheckout constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param ResolverInterface $localeResolver
     * @param RequestBuilder $requestBuilder
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        ResolverInterface $localeResolver,
        RequestBuilder $requestBuilder
    ) {
        $this->resolver = $localeResolver;
        $this->requestBuilder = $requestBuilder;

        parent::__construct($statusResponseManager, $ingenicoClient, $transactionManager, $config);
    }

    /**
     * @param Order $order
     * @throws LocalizedException
     */
    public function create(Order $order)
    {
        $scopeId = $order->getStoreId();
        $amount = DataHelper::formatIngenicoAmount($order->getBaseGrandTotal());
        $currencyCode = $order->getBaseCurrencyCode();
        $countryCode = $order->getBillingAddress()->getCountryId();
        $locale = $this->resolver->getLocale();
        $checkoutSubdomain = $this->ePaymentsConfig->getHostedCheckoutSubdomain($scopeId);

        /** @var InfoInterface $payment */
        $payment = $order->getPayment();

        $paymentProductId = $payment->getAdditionalInformation('ingenico_payment_product_id');

        /** @var PaymentProductResponse $ingenicoPaymentProduct */
        $ingenicoPaymentProduct = $this->ingenicoClient->getIngenicoPaymentProduct(
            $paymentProductId,
            $amount,
            $currencyCode,
            $countryCode,
            $locale,
            $scopeId
        );

        if ($ingenicoPaymentProduct instanceof ErrorResponse) {
            throw new LocalizedException(__('Payment failed: %1', $ingenicoPaymentProduct->errorId));
        }

        $request = $request = $this->requestBuilder->create($order);

        $payment->setAdditionalInformation(
            'ingenico_idempotence_key',
            uniqid(
                preg_replace(
                    '#\s+#',
                    '.',
                    $order->getStoreName()
                ) . '.',
                true
            )
        );

        $response = $this->ingenicoClient->createHostedCheckout($request, $scopeId);
        $ingenicoRedirectUrl = $checkoutSubdomain . $response->partialRedirectUrl;

        $payment->setAdditionalInformation('ingenico_redirect_url', $ingenicoRedirectUrl);
        $payment->setAdditionalInformation('ingenico_hosted_checkout_id', $response->hostedCheckoutId);
        $payment->setAdditionalInformation('ingenico_returnmac', $response->RETURNMAC);
    }
}
