<?php

namespace Ingenico\Connect\WxTransfer;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;

interface ClientInterface
{
    const WX_FILE_PATTERN = "/^w[x,t][t0-9]*\.%010s%d\.(?<version>[\d]{6})\.?(?:%s)?\.xml.gz/";

    /**
     * Attempts to load WxFile for the given date with the configuration of the given scope
     *
     * @param string $date
     * @param int $scopeId
     * @return \DOMDocument|false - the file contents as DomDocument or false if no file was found
     * @throws InputException
     * @throws LocalizedException
     */
    public function loadDailyWx($date, $scopeId);
}
