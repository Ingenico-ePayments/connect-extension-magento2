<?php

namespace Netresearch\Epayments\Block;

use Netresearch\Epayments\Model\Config;

class Info extends \Magento\Payment\Block\Info
{
    /**
     * Init Netresearch epayment info block
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Netresearch_Epayments::info/info.phtml');
    }

    /**
     * Get payment product label
     *
     * @return string|void
     */
    public function getProductLabel()
    {
        $data = $this->getData('info')->getData('additional_information');
        if (!array_key_exists(Config::PRODUCT_LABEL_KEY, $data)) {
            return;
        }
        $label = $data[Config::PRODUCT_LABEL_KEY];
        return  '- '.$label;
    }

    /**
     * @return string
     */
    public function getMethodTitle()
    {
        return $this->getMethod()->getTitle();
    }
}
