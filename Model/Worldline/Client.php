<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline;

use Ingenico\Connect\Sdk\Client as SdkClient;
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
use Ingenico\Connect\Sdk\Domain\Services\TestConnection;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionRequest;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionResponse;
use Ingenico\Connect\Sdk\Domain\Token\CreateTokenResponse;
use Ingenico\Connect\Sdk\Merchant\Productgroups\FindProductgroupsParamsFactory;
use Ingenico\Connect\Sdk\Merchant\Products\GetProductParams;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Client\Communicator\ConfigurationBuilder;
use Worldline\Connect\Model\Worldline\Client\CommunicatorFactory;
use Worldline\Connect\Model\Worldline\Client\CommunicatorLoggerFactory;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse

class Client implements ClientInterface
{
    /**
     * @var ConfigInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $ePaymentsConfig;

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName, SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @var SdkClient[]
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName, SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $worldlineClient = [];

    /**
     * @var CommunicatorLoggerFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $communicatorLoggerFactory;

    /**
     * @var CommunicatorFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $communicatorFactory;

    /**
     * @var ClientFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $clientFactory;

    /**
     * @var DefaultConnectionFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $defaultConnectionFactory;

    /**
     * @var FindProductsParamsFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $findProductsParamsFactory;

    /**
     * @var FindProductgroupsParamsFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $findGroupsParamsFactory;

    /**
     * @var StoreManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $storeManager;

    /**
     * @var RequestInterface | Http
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $request;

    /**
     * @var Client\Communicator\ConfigurationBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        if (!isset($this->worldlineClient[$scopeId])) {
            $this->worldlineClient[$scopeId] = $this->buildFromConfiguration($scopeId);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getWorldlineClient($scopeId = null)
    {
        if ($scopeId === null) {
            $scopeId = $this->storeManager->getStore()->getId();
        }
        $this->initialize($scopeId);

        return $this->worldlineClient[$scopeId];
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

        return $this->worldlineClient[$scopeId]
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->products()
            ->find($findParams);
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint, SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function getPaymentProductGroups($amount, $currencyCode, $countryCode, $locale, $scopeId)
    {
        $findParams = $this->findGroupsParamsFactory->create();
        $findParams->amount = $amount;
        $findParams->currencyCode = $currencyCode;
        $findParams->countryCode = $countryCode;
        $findParams->locale = $locale;

        $this->initialize($scopeId);

        return $this->worldlineClient[$scopeId]
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->productgroups()
            ->find($findParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getWorldlinePaymentProduct(
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
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->products()
            ->get($paymentProductId, $getParams);

        if (!$paymentProduct->id) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Payment failed.'));
        }

        return $paymentProduct;
    }

    /**
     * {@inheritdoc}
     */
    public function worldlinePaymentRefund($refundId, $scopeId = null)
    {
        $refundResponse = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->refunds()
            ->get($refundId);

        return $refundResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function worldlinePaymentCancel($worldlinePaymentId, $scopeId = null)
    {
        $cancelResponse = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->cancel($worldlinePaymentId);

        return $cancelResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function worldlinePaymentAccept($worldlinePaymentId, $scopeId = null)
    {
        $response = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->processchallenged($worldlinePaymentId);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function worldlineRefundAccept($refundId, ApproveRefundRequest $request, $scopeId = null)
    {
        $response = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->refunds()
            ->approve($refundId, $request);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function worldlineRefundCancel($refundId, $scopeId = null)
    {
        $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->refunds()
            ->cancel($refundId);
    }

    /**
     * {@inheritdoc}
     */
    public function worldlineCancelApproval($worldlinePaymentId, $scopeId = null)
    {
        $cancelApproval = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->cancelapproval($worldlinePaymentId);

        return $cancelApproval;
    }

    /**
     * {@inheritdoc}
     */
    public function worldlinePayment($worldlinePaymentId, $scopeId = null)
    {
        $paymentResponse = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->get($worldlinePaymentId);

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
            ->getWorldlineClient($scopeId)
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
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->create($request);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function worldlineRefund($worldlinePaymentId, RefundRequest $request, $callContext, $scopeId = null)
    {
        $response = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->refund($worldlinePaymentId, $request, $callContext);

        return $response;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param string $worldlinePaymentId
     * @param CapturePaymentRequest $request
     * @param int|null $scopeId
     * @return \Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function worldlinePaymentCapture($worldlinePaymentId, CapturePaymentRequest $request, $scopeId = null)
    {
        $response = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->capture($worldlinePaymentId, $request);

        return $response;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param string $worldlinePaymentId
     * @param ApprovePaymentRequest $request
     * @param int|null $scopeId
     * @return \Ingenico\Connect\Sdk\Domain\Payment\PaymentApprovalResponse
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function worldlinePaymentApprove($worldlinePaymentId, ApprovePaymentRequest $request, $scopeId = null)
    {
        $response = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->approve($worldlinePaymentId, $request);

        return $response;
    }

    /**
     * @param SessionRequest $request
     * @param int|null $scopeId
     * @return SessionResponse
     */
    public function worldlineCreateSession(SessionRequest $request, $scopeId = null)
    {
        $response = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->sessions()
            ->create($request);

        return $response;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName, SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @param int|null $scopeId
     * @param Client|null $client
     * @return TestConnection
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName, SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    public function worldlineTestAccount($scopeId = null, ?SdkClient $client = null)
    {
        if ($client === null) {
            $client = $this->getWorldlineClient($scopeId);
        }

        $response = $client
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->services()
            ->testconnection();

        return $response;
    }

    /**
     * @return CreateTokenResponse
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function worldlinePaymentTokenize($worldlinePaymentId, $scopeId = null, $alias = null)
    {
        $tokenizePaymentRequest = new TokenizePaymentRequest();
        if ($alias !== null) {
            $tokenizePaymentRequest->alias = $alias;
        }

        $response = $this
            ->getWorldlineClient($scopeId)
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->payments()
            ->tokenize($worldlinePaymentId, $tokenizePaymentRequest);

        return $response;
    }

    /**
     * @return GetHostedCheckoutResponse
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function getHostedCheckout(string $hostedCheckoutId, $scopeId = null)
    {
        return $this->getWorldlineClient()
            ->merchant($this->ePaymentsConfig->getMerchantId($scopeId))
            ->hostedcheckouts()
            ->get($hostedCheckoutId);
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param $communicatorConfiguration
     * @return SdkClient
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function buildFromConfiguration($scopeId, array $data = [])
    {
        $defaultConnection = $this->defaultConnectionFactory->create();
        $communicator = $this->communicatorFactory
            ->create(
                [
                    'connection' => $defaultConnection,
                    'communicatorConfiguration' => $this->configurationBuilder->build($scopeId, $data),
                ]
            );

        $clientMetaInfo = [
            'platformIdentifier' => $this->request->getServer('HTTP_USER_AGENT'),
        ];
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $client = $this->clientFactory->create($communicator, json_encode($clientMetaInfo));

        if ($this->ePaymentsConfig->getLogAllRequests()) {
            $communicatorLogger = $this->communicatorLoggerFactory
                ->create();
            $client->enableLogging($communicatorLogger);
        }
        return $client;
    }
}
