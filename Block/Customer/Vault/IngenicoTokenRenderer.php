<?php

declare(strict_types=1);

namespace Ingenico\Connect\Block\Customer\Vault;

use Ingenico\Connect\Model\ConfigProvider;
use InvalidArgumentException;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;
use function array_key_exists;
use function sprintf;

class IngenicoTokenRenderer extends AbstractCardRenderer
{
    public function getNumberLast4Digits()
    {
        return $this->getArrayKey($this->getTokenDetails(), 'card');
    }

    public function getExpDate()
    {
        return $this->getArrayKey($this->getTokenDetails(), 'expiry');
    }

    public function getIconUrl()
    {
        return $this->getArrayKey($this->getIconForType($this->getType()), 'url');
    }

    public function getIconHeight()
    {
        return $this->getArrayKey($this->getIconForType($this->getType()), 'height');
    }

    public function getIconWidth()
    {
        return $this->getArrayKey($this->getIconForType($this->getType()), 'width');
    }

    public function canRender(PaymentTokenInterface $token)
    {
        return $token->getPaymentMethodCode() === ConfigProvider::CODE;
    }

    private function getType(): string
    {
        return (string) $this->getArrayKey($this->getTokenDetails(), 'type');
    }

    private function getArrayKey(array $array, string $key)
    {
        if (!array_key_exists($key, $array)) {
            throw new InvalidArgumentException(sprintf('Could not find key "%s" in array', $key));
        }

        return $array[$key];
    }
}
