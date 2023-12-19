<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field;

use Worldline\Connect\Model\Config;

class TestApiConnectionPreProduction extends TestApiConnection
{
    public function getAjaxUrl(): string
    {
        return $this->getUrl('epayments/Api/TestConnection', [
            'environment' => Config::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD,
        ]);
    }

    public function getId(): string
    {
        return 'test_api_connection_pre_production';
    }
}
