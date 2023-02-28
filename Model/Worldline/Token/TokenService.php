<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Token;

use DateTime;
use Ingenico\Connect\Sdk\Domain\Definitions\CardEssentials;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificOutput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Model\Order;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Model\PaymentTokenRepository;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Worldline\Connect\Model\ConfigProvider;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;

use function in_array;
use function json_encode;

class TokenService implements TokenServiceInterface
{
    /**
     * @var PaymentTokenManagementInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenRepository
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentTokenRepository;

    /**
     * @var ClientInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $client;

    /**
     * @var PaymentTokenFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentTokenFactory;

    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepository $paymentTokenRepository,
        ClientInterface $client,
        PaymentTokenFactory $paymentTokenFactory
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->client = $client;
        $this->paymentTokenFactory = $paymentTokenFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function find($customerId)
    {
        $tokens = [];

        // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
        /** @var PaymentTokenInterface[] $paymentTokens */
        $paymentTokens = $this->paymentTokenManagement->getVisibleAvailableTokens($customerId);
        foreach ($paymentTokens as $paymentToken) {
            if ($paymentToken->getIsActive() && $paymentToken->getIsVisible()) {
                $tokens[] = $paymentToken->getGatewayToken();
            }
        }

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return array_unique($tokens);
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param int $customerId
     * @param array $tokens
     * @throws \Exception
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function deleteAll($customerId, $tokens = [])
    {
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        if ($customerId && !empty($tokens)) {
            // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
            /** @var PaymentTokenInterface[] $paymentTokens */
            $paymentTokens = $this->paymentTokenManagement->getVisibleAvailableTokens($customerId);
            foreach ($paymentTokens as $paymentToken) {
                if (in_array($paymentToken->getGatewayToken(), $tokens)) {
                    $this->paymentTokenRepository->delete($paymentToken);
                }
            }
        }
    }

    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function createByOrderAndPayment(Order $order, Payment $payment)
    {
        if (!$order->getPayment()->getAdditionalInformation('tokenize')) {
            return;
        }

        $customerId = $order->getCustomerId();
        if (!$customerId) {
            return;
        }

        $paymentOutput = $payment->paymentOutput;
        if ($paymentOutput === null) {
            return;
        }

        $cardPaymentMethodSpecificOutput = $paymentOutput->cardPaymentMethodSpecificOutput;
        if ($cardPaymentMethodSpecificOutput === null) {
            return;
        }

        $card = $paymentOutput->cardPaymentMethodSpecificOutput->card;
        $alias = $card->cardNumber;
        $tokenResponse = $this->client->worldlinePaymentTokenize(
            $payment->id,
            null,
            $alias
        );
        $token = $tokenResponse->token;
        if ($token === null) {
            return;
        }

        $paymentToken = $this->paymentTokenManagement->getByGatewayToken($token, ConfigProvider::CODE, $customerId);
        if ($paymentToken !== null) {
            return;
        }

        $orderPayment = $order->getPayment();
        $orderPayment->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, 1);
        $orderPayment->getExtensionAttributes()->setVaultPaymentToken(
            $this->buildPaymentToken($cardPaymentMethodSpecificOutput, $card, $token, $alias)
        );
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param CardPaymentMethodSpecificOutput $cardPaymentMethodSpecificOutput
     * @param CardEssentials $card
     * @param $token
     * @param $alias
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function buildPaymentToken(
        CardPaymentMethodSpecificOutput $cardPaymentMethodSpecificOutput,
        CardEssentials $card,
        $token,
        $alias
    ) {
        $paymentProductId = $cardPaymentMethodSpecificOutput->paymentProductId;
        $schemeTransactionId = $cardPaymentMethodSpecificOutput->schemeTransactionId;

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setExpiresAt((DateTime::createFromFormat('my', $card->expiryDate))->format('Y-m-1 00:00:00'));
        $paymentToken->setGatewayToken($token);
        $paymentToken->setTokenDetails(json_encode([
            'alias' => $alias,
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            'card' => substr($card->cardNumber, -4),
            'expiry' => (DateTime::createFromFormat('my', $card->expiryDate))->format('m/y'),
            'paymentProductId' => $paymentProductId,
            'transactionId' => $schemeTransactionId,
        ]));

        return $paymentToken;
    }
}
