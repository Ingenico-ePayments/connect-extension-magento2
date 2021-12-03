<?php

namespace Ingenico\Connect\TokenProvider;

use Ingenico\Connect\Model\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
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
     * @param TokenUiComponentInterfaceFactory $componentFactory
     */
    public function __construct(TokenUiComponentInterfaceFactory $componentFactory)
    {
        $this->componentFactory = $componentFactory;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken): TokenUiComponentInterface
    {
        return $this->componentFactory->create(
            [
                'config' => [
                    'code' => ConfigProvider::CODE,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => json_decode(
                        $paymentToken->getTokenDetails() ?: '{}',
                        true
                    ),
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                    'template' => 'Ingenico_Connect::form/vault-item.phtml'
                ],
                'name' => Template::class
            ]
        );
    }
}
