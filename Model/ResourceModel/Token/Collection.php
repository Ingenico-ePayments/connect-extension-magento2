<?php

namespace Ingenico\Connect\Model\ResourceModel\Token;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Ingenico\Connect\Model\Ingenico\Token\Token::class,
            \Ingenico\Connect\Model\ResourceModel\Token::class
        );
    }

    /**
     * Filter by customer id
     *
     * @param $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->addFieldToFilter('customer_id', $customerId);

        return $this;
    }

    /**
     * Filter by payment product
     *
     * @param $paymentProductId
     * @return $this
     */
    public function setPaymentProductId($paymentProductId)
    {
        $this->addFieldToFilter('payment_product_id', $paymentProductId);

        return $this;
    }

    /**
     * Filter by token
     *
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->addFieldToFilter('token', $token);

        return $this;
    }
}
