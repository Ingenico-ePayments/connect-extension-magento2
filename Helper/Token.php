<?php

declare(strict_types=1);

namespace Worldline\Connect\Helper;

use DateTime;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificOutput;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Model\PaymentTokenFactory;

use function json_encode;
use function substr;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock

class Token
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
     * @var PaymentTokenFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentTokenFactory;

    public function __construct(PaymentTokenFactory $paymentTokenFactory)
    {
        $this->paymentTokenFactory = $paymentTokenFactory;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param CardPaymentMethodSpecificOutput $cardPaymentMethodSpecificOutput
     * @param $token
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function buildPaymentToken(CardPaymentMethodSpecificOutput $cardPaymentMethodSpecificOutput, $token)
    {
        $paymentProductId = $cardPaymentMethodSpecificOutput->paymentProductId;
        $card = $cardPaymentMethodSpecificOutput->card;

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setExpiresAt((DateTime::createFromFormat('my', $card->expiryDate))->format('Y-m-1 00:00:00'));
        $paymentToken->setGatewayToken($token);
        $paymentToken->setTokenDetails(json_encode([
            'card' => substr($card->cardNumber, -4),
            'expiry' => (DateTime::createFromFormat('my', $card->expiryDate))->format('m/y'),
            'type' => self::MAP[$paymentProductId] ?: null,
        ]));

        return $paymentToken;
    }
}
