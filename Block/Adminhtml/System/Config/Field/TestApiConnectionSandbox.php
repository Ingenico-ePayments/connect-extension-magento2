<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field;

use Worldline\Connect\Model\Config;

class TestApiConnectionSandbox extends TestApiConnection
{
    public function getAjaxUrl(): string
    {
        return $this->getUrl('epayments/Api/TestConnection', [
            'environment' => Config::ENVIRONMENT_SANDBOX,
        ]);
    }

    public function getId(): string
    {
        return 'test_api_connection_sandbox';
    }
}
