<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

use function json_decode;

// phpcs:ignore PSR12.Files.FileHeader.SpacingAfterBlock

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $componentFactory;

    /**
     * @var string
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $code;

    /**
     * @param TokenUiComponentInterfaceFactory $componentFactory
     */
    public function __construct(TokenUiComponentInterfaceFactory $componentFactory, string $code)
    {
        $this->componentFactory = $componentFactory;
        $this->code = $code;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        return $this->componentFactory->create(
            [
                'config' => [
                    'code' => $this->code,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => json_decode(
                        $paymentToken->getTokenDetails() ?: '{}',
                        true
                    ),
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                ],
                // phpcs:ignore SlevomatCodingStandard.Arrays.TrailingArrayComma.MissingTrailingComma
                'name' => 'Worldline_Connect/js/view/payment/method-renderer/vault'
            ]
        );
    }
}
