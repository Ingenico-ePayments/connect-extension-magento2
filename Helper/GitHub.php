<?php

declare(strict_types=1);

namespace Ingenico\Connect\Helper;

use function array_key_exists;
use Exception;
use Ingenico\Connect\Model\ConfigInterface;
use Magento\Framework\HTTP\ClientInterfaceFactory;
use Psr\Log\LoggerInterface;
use function sprintf;

class GitHub
{
    public function getRepositoryUrl(): string
    {
        return sprintf('https://github.com/%s', $this->getRepositoryName());
    }
    
    public function getApiUrl(): string
    {
        return sprintf('https://api.github.com/repos/%s', $this->getRepositoryName());
    }
    
    public function getRepositoryName(): string
    {
        return 'Ingenico-ePayments/connect-extension-magento2';
    }
}
