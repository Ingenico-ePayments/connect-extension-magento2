<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Customer\Vault;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;

use function str_starts_with;

class WorldlineTokenRenderer extends AbstractCardRenderer
{
    public function getNumberLast4Digits(): string
    {
        return (string) $this->getTokenDetails()['card'];
    }

    public function getExpDate(): string
    {
        return (string) $this->getTokenDetails()['expiry'];
    }

    public function getIconUrl(): string
    {
        return (string) $this->getIconForType($this->getTokenDetails()['type'])['url'];
    }

    public function getIconHeight(): string
    {
        return (string) $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    public function getIconWidth(): string
    {
        return (string) $this->getIconForType($this->getTokenDetails()['type'])['width'];
    }

    public function canRender(PaymentTokenInterface $token): bool
    {
        return str_starts_with($token->getPaymentMethodCode(), 'worldline_');
    }
}
