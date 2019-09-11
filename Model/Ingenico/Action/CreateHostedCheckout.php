<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Errors\ErrorResponse;
use Ingenico\Connect\Sdk\Domain\Product\PaymentProductResponse;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Helper\Data as DataHelper;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\CreateHostedCheckout\RequestBuilder;
use Ingenico\Connect\Model\StatusResponseManager;
use Ingenico\Connect\Model\Transaction\TransactionManager;

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
        $checkoutSubdomain = $this->ePaymentsConfig->getHostedCheckoutSubDomain($scopeId);
        $shouldHavePaymentProduct = $this->ePaymentsConfig
                ->getCheckoutType($scopeId) !== Config::CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT;

        /** @var InfoInterface $payment */
        $payment = $order->getPayment();

        if ($shouldHavePaymentProduct) {
            $paymentProductId = $payment->getAdditionalInformation(Config::PRODUCT_ID_KEY);
            try {
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
            } catch (ResponseException $exception) {
                throw new LocalizedException(
                    __(
                        'Payment failed for payment product with id %1: %2',
                        $paymentProductId,
                        $exception->getMessage()
                    )
                );
            }
        }

        $request = $this->requestBuilder->create($order);

        $payment->setAdditionalInformation(
            Config::IDEMPOTENCE_KEY,
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

        $payment->setAdditionalInformation(Config::REDIRECT_URL_KEY, $ingenicoRedirectUrl);
        $payment->setAdditionalInformation(Config::HOSTED_CHECKOUT_ID_KEY, $response->hostedCheckoutId);
        $payment->setAdditionalInformation(Config::RETURNMAC_KEY, $response->RETURNMAC);
    }
}
