<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Token;

use Magento\Framework\Model\AbstractModel;

class Token extends AbstractModel
{
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _construct()
    {
        $this->_init('Worldline\Connect\Model\ResourceModel\Token');
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
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
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
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
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
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function setToken($token)
    {
        $this->setData('token', $token);

        return $this;
    }
}
