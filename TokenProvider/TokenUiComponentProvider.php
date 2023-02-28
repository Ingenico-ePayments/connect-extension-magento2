<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\TokenProvider;

use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Worldline\Connect\Model\ConfigProvider;

use function json_decode;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    public function __construct(
        private readonly TokenUiComponentInterfaceFactory $componentFactory
    ) {
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
                    'template' => 'Worldline_Connect::form/vault-item.phtml',
                ],
                'name' => Template::class,
            ]
        );
    }
}
