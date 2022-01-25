<?php

namespace Ingenico\Connect\Model\Ingenico\Token;

use DateTime;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificOutput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Model\Order;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentTokenFactory;
use Magento\Vault\Model\PaymentTokenRepository;
use Magento\Vault\Model\Ui\VaultConfigProvider;

use function in_array;
use function json_encode;
use function substr;

class TokenService implements TokenServiceInterface
{
    private const MAP = [
        2 => 'AE',
        146 => 'AU',
        132 => 'DN',
        128 => 'DI',
        163 => 'HC',
        125 => 'JCB',
        117 => 'SM',
        3 => 'MC',
        119 => 'MC',
        1 => 'VI',
        114 => 'VI',
        122 => 'VI',
    ];

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var PaymentTokenFactory
     */
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

        /** @var PaymentTokenInterface[] $paymentTokens */
        $paymentTokens = $this->paymentTokenManagement->getVisibleAvailableTokens($customerId);
        foreach ($paymentTokens as $paymentToken) {
            if ($paymentToken->getIsActive() && $paymentToken->getIsVisible()) {
                $tokens[] = $paymentToken->getGatewayToken();
            }
        }

        return array_unique($tokens);
    }

    /**
     * @param int $customerId
     * @param array $tokens
     * @throws \Exception
     */
    public function deleteAll($customerId, $tokens = [])
    {
        if ($customerId && !empty($tokens)) {
            /** @var PaymentTokenInterface[] $paymentTokens */
            $paymentTokens = $this->paymentTokenManagement->getVisibleAvailableTokens($customerId);
            foreach ($paymentTokens as $paymentToken) {
                if (in_array($paymentToken->getGatewayToken(), $tokens)) {
                    $this->paymentTokenRepository->delete($paymentToken);
                }
            }
        }
    }

    public function createByOrderAndPayment(Order $order, Payment $payment)
    {
        if (!$order->getPayment()->getAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY)) {
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

        $tokenResponse = $this->client->ingenicoPaymentTokenize($payment->id);
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
            $this->buildPaymentToken($cardPaymentMethodSpecificOutput, $token)
        );
    }

    /**
     * @param CardPaymentMethodSpecificOutput $cardPaymentMethodSpecificOutput
     * @param $token
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    public function buildPaymentToken(CardPaymentMethodSpecificOutput $cardPaymentMethodSpecificOutput, $token)
    {
        $paymentProductId = $cardPaymentMethodSpecificOutput->paymentProductId;
        $card = $cardPaymentMethodSpecificOutput->card;
        $schemeTransactionId = $cardPaymentMethodSpecificOutput->schemeTransactionId;

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setExpiresAt((DateTime::createFromFormat('my', $card->expiryDate))->format('Y-m-1 00:00:00'));
        $paymentToken->setGatewayToken($token);
        $paymentToken->setTokenDetails(json_encode([
            'card' => substr($card->cardNumber, -4),
            'expiry' => (DateTime::createFromFormat('my', $card->expiryDate))->format('m/y'),
            'type' => self::MAP[$paymentProductId] ?: null,
            'transactionId' => $schemeTransactionId,
        ]));

        return $paymentToken;
    }
}
