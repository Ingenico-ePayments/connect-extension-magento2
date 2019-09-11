<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\Device;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\BrowserData;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\BrowserDataFactory;

class BrowserDataBuilder
{
    /**
     * @var BrowserDataFactory
     */
    private $browserDataFactory;

    public function __construct(BrowserDataFactory $browserDataFactory)
    {
        $this->browserDataFactory = $browserDataFactory;
    }

    public function create(): BrowserData
    {
        $browserData = $this->browserDataFactory->create();

        $browserData->javaScriptEnabled = true;

        return $browserData;
    }
}
