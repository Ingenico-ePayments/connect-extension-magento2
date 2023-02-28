<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field;

use Worldline\Connect\Model\Config;

class TestApiConnectionPreProduction extends TestApiConnection
{
    public function getAjaxUrl(): string
    {
        return $this->getUrl('epayments/Api/TestConnection', [
            // phpcs:ignore SlevomatCodingStandard.Arrays.TrailingArrayComma.MissingTrailingComma
            'environment' => Config::ENVIRONMENT_PRE_PRODUCTION
        ]);
    }

    public function getId(): string
    {
        return 'test_api_connection_pre_production';
    }
}
