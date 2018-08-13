<?php

namespace Netresearch\Epayments\Model\Ingenico\Token;

use Magento\Framework\Model\AbstractModel;

class Token extends AbstractModel
{
    public function _construct()
    {
        $this->_init('Netresearch\Epayments\Model\ResourceModel\Token');
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * @param $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->setData('customer_id', $customerId);

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentProductId()
    {
        return $this->getData('payment_product_id');
    }

    /**
     * @param $paymentProductId
     * @return $this
     */
    public function setPaymentProductId($paymentProductId)
    {
        $this->setData('payment_product_id', $paymentProductId);

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->getData('token');
    }

    /**
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->setData('token', $token);

        return $this;
    }
}
