<?php

namespace Ingenico\Connect\Model\Ingenico;

use Ingenico\Connect\Sdk\DefaultConnectionFactory;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\ApprovePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CapturePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentResponse;
use Ingenico\Connect\Sdk\Domain\Product\PaymentProductResponse;
use Ingenico\Connect\Sdk\Domain\Refund\ApproveRefundRequest;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionRequest;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionResponse;
use Ingenico\Connect\Sdk\Merchant\Products\GetProductParams;
use Ingenico\Connect\Sdk\Merchant\Products\GetProductParamsFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\Client\CommunicatorConfigurationFactory;
use Ingenico\Connect\Model\Ingenico\Client\CommunicatorFactory;
use Ingenico\Connect\Model\Ingenico\Client\CommunicatorLoggerFactory;
use Ingenico\Connect\Model\Ingenico\Client\ShoppingCartExtensionFactory;

class Client implements ClientInterface
{
    /** @var ConfigInterface */
    private $ePaymentsConfig;

    /** @var \Ingenico\Connect\Sdk\Client[] */
    private $ingenicoClient = [];

    /**
     * @var ShoppingCartExtensionFactory
     */
    private $shoppingCartExtensionFactory;

    /** @var CommunicatorLoggerFactory */
    private $communicatorLoggerFactory;

    /** @var CommunicatorConfigurationFactory */
    private $communicatorConfigurationFactory;

    /** @var CommunicatorFactory */
    private $communicatorFactory;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var DefaultConnectionFactory */
    private $defaultConnectionFactory;

    /** @var FindProductsParamsFactory */
    private $findProductParamsFactory;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\RequestInterface | Http
     */
    private $request;

    /**
     * Client constructor.
     *
     * @param ConfigInterface $ePaymentsConfig
     * @param ShoppingCartExtensionFactory $shoppingCartExtensionFactory
     * @param CommunicatorLoggerFactory $communicatorLoggerFactory
     * @param CommunicatorConfigurationFactory $communicatorConfigurationFactory
     * @param CommunicatorFactory $communicatorFactory
     * @param ClientFactory $clientFactory
     * @param DefaultConnectionFactory $defaultConnectionFactory
     * @param GetProductParamsFactory $getProductParamsFactory
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        ConfigInterface $ePaymentsConfig,
        ShoppingCartExtensionFactory $shoppingCartExtensionFactory,
        CommunicatorLoggerFactory $communicatorLoggerFactory,
        CommunicatorConfigurationFactory $communicatorConfigurationFactory,
        CommunicatorFactory $communicatorFactory,
        ClientFactory $clientFactory,
        DefaultConnectionFactory $defaultConnectionFactory,
        GetProductParamsFactory $getProductParamsFactory,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->shoppingCartExtensionFactory = $shoppingCartExtensionFactory;
        $this->communicatorLoggerFactory = $communicatorLoggerFactory;
        $this->communicatorConfigurationFactory = $communicatorConfigurationFactory;
        $this->communicatorFactory = $communicatorFactory;
        $this->clientFactory = $clientFactory;
        $this->findProductParamsFactory = $getProductParamsFactory;
        $this->defaultConnectionFactory = $defaultConnectionFactory;
        $this->storeManager = $storeManager;
        $this->request = $request;
    }

    /**
     * Initialize Client object
     *
     * @param int $scopeId
     */
    private function initialize($scopeId)
    {
        if (!isset($this->ingenicoClient[$scopeId])) {
            $config = $this->buildCommunicatorConfiguration($scopeId);

            $secondaryConfig = $this->buildCommunicatorConfiguration(
                $scopeId,
                [
                    'api_endpoint' => $this->ePaymentsConfig->getSecondaryApiEndpoint(
                        $scopeId
                    ),
                ]
            );
            $this->ingenicoClient[$scopeId] = $this->buildFromConfiguration(
                $config,
                $secondaryConfig
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getIngenicoClient($scopeId = null)
    {
        if ($scopeId === null) {
            $scopeId = $this->storeManager->getStore()->getId();
        }
        $this->initialize($scopeId);

        return $this->ingenicoClient[$scopeId];
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailablePaymentProducts($amount, $currencyCode, $countryCode, $locale, $scopeId)
    {
        $findParams = $this->findProductParamsFactory->create();
        $findParams->amount = $amount;
        $findParams->currencyCode = $currencyCode;
        $findParams->countryCode = $countryCode;
        $findParams->locale = $locale;
        $findParams->hide = 'fields';

        $this->initialize($scopeId);

        return $this->ingenicoClient[$scopeId]
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->products()
            ->find($findParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getIngenicoPaymentProduct(
        $paymentProductId,
        $amount,
        $currencyCode,
        $countryCode,
        $locale,
        $scopeId
    ) {
        $getParams = new GetProductParams();
        $getParams->amount = $amount;
        $getParams->currencyCode = $currencyCode;
        $getParams->countryCode = $countryCode;
        $getParams->locale = $locale;
        $getParams->hide = 'fields';

        /** @var PaymentProductResponse $paymentProduct */
        $paymentProduct = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->products()
            ->get($paymentProductId, $getParams);

        if (!$paymentProduct->id) {
            throw new LocalizedException(__('Payment failed.'));
        }

        return $paymentProduct;
    }

    /**
     * {@inheritdoc}
     */
    public function ingenicoPaymentRefund($refundId, $scopeId = null)
    {
        $refundResponse = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->refunds()
            ->get($refundId);

        return $refundResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function ingenicoPaymentCancel($ingenicoPaymentId, $scopeId = null)
    {
        $cancelResponse = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->cancel($ingenicoPaymentId);

        return $cancelResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function ingenicoPaymentAccept($ingenicoPaymentId, $scopeId = null)
    {
        $response = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->processchallenged($ingenicoPaymentId);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function ingenicoRefundAccept($refundId, ApproveRefundRequest $request, $scopeId = null)
    {
        $response = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->refunds()
            ->approve($refundId, $request);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function ingenicoRefundCancel($refundId, $scopeId = null)
    {
        $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->refunds()
            ->cancel($refundId);
    }

    /**
     * {@inheritdoc}
     */
    public function ingenicoCancelApproval($ingenicoPaymentId, $scopeId = null)
    {
        $cancelApproval = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->cancelapproval($ingenicoPaymentId);

        return $cancelApproval;
    }

    /**
     * {@inheritdoc}
     */
    public function ingenicoPayment($ingenicoPaymentId, $scopeId = null)
    {
        $paymentResponse = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->get($ingenicoPaymentId);

        return $paymentResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function createHostedCheckout(
        CreateHostedCheckoutRequest $paymentProductRequest,
        $scopeId = null
    ) {
        $response = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->hostedcheckouts()
            ->create($paymentProductRequest);

        return $response;
    }

    /**
     * @param CreatePaymentRequest $request
     * @param int|null $scopeId
     * @return CreatePaymentResponse
     */
    public function createPayment(CreatePaymentRequest $request, $scopeId = null)
    {
        $response = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->create($request);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function ingenicoRefund($ingenicoPaymentId, RefundRequest $request, $callContext, $scopeId = null)
    {
        $response = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->refund($ingenicoPaymentId, $request, $callContext);

        return $response;
    }

    /**
     * @param string $ingenicoPaymentId
     * @param CapturePaymentRequest $request
     * @param int|null $scopeId
     * @return \Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse
     */
    public function ingenicoPaymentCapture($ingenicoPaymentId, CapturePaymentRequest $request, $scopeId = null)
    {
        $response = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->capture($ingenicoPaymentId, $request);

        return $response;
    }

    /**
     * @param string $ingenicoPaymentId
     * @param ApprovePaymentRequest $request
     * @param int|null $scopeId
     * @return \Ingenico\Connect\Sdk\Domain\Payment\PaymentApprovalResponse
     */
    public function ingenicoPaymentApprove($ingenicoPaymentId, ApprovePaymentRequest $request, $scopeId = null)
    {
        $response = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->approve($ingenicoPaymentId, $request);

        return $response;
    }

    /**
     * @param SessionRequest $request
     * @param int|null $scopeId
     * @return SessionResponse
     */
    public function ingenicoCreateSession(SessionRequest $request, $scopeId = null)
    {
        $response = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->sessions()
            ->create($request);

        return $response;
    }

    /**
     * @param int|null $scopeId
     * @param string[] $data
     * @return \Ingenico\Connect\Sdk\Domain\Services\TestConnection
     */
    public function ingenicoTestAccount($scopeId = null, $data = [])
    {
        $client = $this->buildFromConfiguration($this->buildCommunicatorConfiguration($scopeId, $data));
        $response = $client
            ->merchant($data['merchant_id'])
            ->services()
            ->testconnection();

        return $response;
    }

    /**
     * @param $scopeId
     * @param string[] $data
     * @return \Ingenico\Connect\Sdk\CommunicatorConfiguration
     */
    private function buildCommunicatorConfiguration($scopeId, $data = [])
    {
        $cartExtension = $this->shoppingCartExtensionFactory->create(
            $this->ePaymentsConfig->getIntegrator(),
            $this->ePaymentsConfig->getShoppingCartExtensionName(),
            $this->ePaymentsConfig->getVersion()
        );

        $apiKey = !empty($data['api_key']) ? $data['api_key'] : $this->ePaymentsConfig->getApiKey($scopeId);
        $apiSecret = !empty($data['api_secret']) ? $data['api_secret'] : $this->ePaymentsConfig->getApiSecret($scopeId);
        $apiEndpoint = !empty($data['api_endpoint']) ?
            $data['api_endpoint'] : $this->ePaymentsConfig->getApiEndpoint($scopeId);

        $configuration = $this->communicatorConfigurationFactory
            ->create(
                $apiKey,
                $apiSecret,
                $apiEndpoint,
                $this->ePaymentsConfig->getIntegrator()
            );

        $configuration->setShoppingCartExtension($cartExtension);
        return $configuration;
    }

    /**
     * @param $communicatorConfiguration
     * @param $secondaryCommunicatorConfiguration
     * @return \Ingenico\Connect\Sdk\Client
     */
    private function buildFromConfiguration($communicatorConfiguration, $secondaryCommunicatorConfiguration = null)
    {
        $defaultConnection = $this->defaultConnectionFactory->create();
        $communicator = $this->communicatorFactory
            ->create(
                [
                    'connection' => $defaultConnection,
                    'communicatorConfiguration' => $communicatorConfiguration,
                ]
            );
        if ($secondaryCommunicatorConfiguration !== null) {
            $communicator->setSecondaryCommunicatorConfiguration($secondaryCommunicatorConfiguration);
        }

        $clientMetaInfo = [
            'platformIdentifier' => $this->request->getServer('HTTP_USER_AGENT'),
        ];
        $client = $this->clientFactory->create($communicator, json_encode($clientMetaInfo));

        if ($this->ePaymentsConfig->getLogAllRequests()) {
            $communicatorLogger = $this->communicatorLoggerFactory
                ->create();
            $client->enableLogging($communicatorLogger);
        }
        return $client;
    }
}
