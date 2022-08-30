<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use function json_decode;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var string
     */
    private $code;

    /**
     * @param TokenUiComponentInterfaceFactory $componentFactory
     */
    public function __construct(TokenUiComponentInterfaceFactory $componentFactory, string $code)
    {
        $this->componentFactory = $componentFactory;
        $this->code = $code;
    }

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
                'name' => 'Ingenico_Connect/js/view/payment/method-renderer/vault'
            ]
        );
    }
}
