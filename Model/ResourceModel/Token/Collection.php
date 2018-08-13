<?php

namespace Netresearch\Epayments\Model\ResourceModel\Token;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Netresearch\Epayments\Model\Ingenico\Token\Token::class,
            \Netresearch\Epayments\Model\ResourceModel\Token::class
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
