<?php

namespace Ingenico\Connect\Model\Ingenico;

use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\Client\Communicator\ConfigurationBuilder;
use Ingenico\Connect\Model\Ingenico\Client\CommunicatorFactory;
use Ingenico\Connect\Model\Ingenico\Client\CommunicatorLoggerFactory;
use Ingenico\Connect\Sdk\DefaultConnectionFactory;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\GetHostedCheckoutResponse;
use Ingenico\Connect\Sdk\Domain\Payment\ApprovePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CapturePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentResponse;
use Ingenico\Connect\Sdk\Domain\Payment\TokenizePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Product\PaymentProductResponse;
use Ingenico\Connect\Sdk\Domain\Refund\ApproveRefundRequest;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionRequest;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionResponse;
use Ingenico\Connect\Sdk\Domain\Token\CreateTokenResponse;
use Ingenico\Connect\Sdk\Merchant\Productgroups\FindProductgroupsParamsFactory;
use Ingenico\Connect\Sdk\Merchant\Products\GetProductParams;
use Ingenico\Connect\Sdk\Merchant\Products\GetProductParamsFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Client implements ClientInterface
{
    /**
     * @var ConfigInterface
     */
    private $ePaymentsConfig;

    /**
     * @var \Ingenico\Connect\Sdk\Client[]
     */
    private $ingenicoClient = [];

    /**
     * @var CommunicatorLoggerFactory
     */
    private $communicatorLoggerFactory;

    /**
     * @var CommunicatorFactory
     */
    private $communicatorFactory;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var DefaultConnectionFactory
     */
    private $defaultConnectionFactory;

    /**
     * @var FindProductsParamsFactory
     */
    private $findProductsParamsFactory;

    /**
     * @var FindProductgroupsParamsFactory
     */
    private $findGroupsParamsFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var RequestInterface | Http
     */
    private $request;

    /**
     * @var Client\Communicator\ConfigurationBuilder
     */
    private $configurationBuilder;

    /**
     * Client constructor.
     *
     * @param ConfigInterface $ePaymentsConfig
     * @param CommunicatorLoggerFactory $communicatorLoggerFactory
     * @param ConfigurationBuilder $configurationBuilder
     * @param CommunicatorFactory $communicatorFactory
     * @param ClientFactory $clientFactory
     * @param DefaultConnectionFactory $defaultConnectionFactory
     * @param FindProductgroupsParamsFactory $findGroupsParamsFactory
     * @param FindProductsParamsFactory $findProductsParamsFactory
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     */
    public function __construct(
        ConfigInterface $ePaymentsConfig,
        CommunicatorLoggerFactory $communicatorLoggerFactory,
        ConfigurationBuilder $configurationBuilder,
        CommunicatorFactory $communicatorFactory,
        ClientFactory $clientFactory,
        DefaultConnectionFactory $defaultConnectionFactory,
        FindProductgroupsParamsFactory $findGroupsParamsFactory,
        FindProductsParamsFactory $findProductsParamsFactory,
        StoreManagerInterface $storeManager,
        RequestInterface $request
    ) {
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->communicatorLoggerFactory = $communicatorLoggerFactory;
        $this->communicatorFactory = $communicatorFactory;
        $this->clientFactory = $clientFactory;
        $this->defaultConnectionFactory = $defaultConnectionFactory;
        $this->findGroupsParamsFactory = $findGroupsParamsFactory;
        $this->findProductsParamsFactory = $findProductsParamsFactory;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->configurationBuilder = $configurationBuilder;
    }

    /**
     * Initialize Client object
     *
     * @param int $scopeId
     */
    private function initialize($scopeId)
    {
        if (!isset($this->ingenicoClient[$scopeId])) {
            $config = $this->configurationBuilder->build($scopeId);
            $this->ingenicoClient[$scopeId] = $this->buildFromConfiguration($config);
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
        $findParams = $this->findProductsParamsFactory->create();
        $findParams->amount = $amount;
        $findParams->currencyCode = $currencyCode;
        $findParams->countryCode = $countryCode;
        $findParams->locale = $locale;

        $this->initialize($scopeId);

        return $this->ingenicoClient[$scopeId]
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->products()
            ->find($findParams);
    }

    public function getPaymentProductGroups($amount, $currencyCode, $countryCode, $locale, $scopeId)
    {
        $findParams = $this->findGroupsParamsFactory->create();
        $findParams->amount = $amount;
        $findParams->currencyCode = $currencyCode;
        $findParams->countryCode = $countryCode;
        $findParams->locale = $locale;

        $this->initialize($scopeId);

        return $this->ingenicoClient[$scopeId]
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->productgroups()
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
        $client = $this->buildFromConfiguration($this->configurationBuilder->build($scopeId, $data));
        $response = $client
            ->merchant($data['merchant_id'])
            ->services()
            ->testconnection();

        return $response;
    }

    /**
     * @return CreateTokenResponse
     */
    public function ingenicoPaymentTokenize($ingenicoPaymentId, $scopeId = null, $alias = null)
    {
        $tokenizePaymentRequest = new TokenizePaymentRequest();
        if ($alias !== null) {
            $tokenizePaymentRequest->alias = $alias;
        }

        $response = $this
            ->getIngenicoClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->tokenize($ingenicoPaymentId, $tokenizePaymentRequest);

        return $response;
    }

    /**
     * @return GetHostedCheckoutResponse
     */
    public function getHostedCheckout(string $hostedCheckoutId, $scopeId = null)
    {
        return $this->getIngenicoClient()
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->hostedcheckouts()
            ->get($hostedCheckoutId);
    }

    /**
     * @param $communicatorConfiguration
     * @return \Ingenico\Connect\Sdk\Client
     */
    private function buildFromConfiguration($communicatorConfiguration)
    {
        $defaultConnection = $this->defaultConnectionFactory->create();
        $communicator = $this->communicatorFactory
            ->create(
                [
                    'connection' => $defaultConnection,
                    'communicatorConfiguration' => $communicatorConfiguration,
                ]
            );

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
