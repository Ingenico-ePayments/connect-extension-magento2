<?php

declare(strict_types=1);

namespace Worldline\Connect\Block;

use Magento\Payment\Block\Info as BaseInfo;
use Worldline\Connect\Model\Config;

use function array_key_exists;

class Info extends BaseInfo
{
    /**
     * Init Worldline epayment info block
     */
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Worldline_Connect::info/info.phtml');
    }

    public function getProductLabel(): string
    {
        $data = $this->getData('info')->getData('additional_information');

        return array_key_exists(Config::PRODUCT_LABEL_KEY, $data) ?  '- ' . $data[Config::PRODUCT_LABEL_KEY] : '';
    }

    public function getMethodTitle(): string
    {
        return (string) $this->getMethod()->getTitle();
    }
}
