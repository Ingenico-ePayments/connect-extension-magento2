<?php

declare(strict_types=1);

namespace Worldline\Connect\Helper;

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
