<?php

namespace Netresearch\Epayments\Model\Ingenico\Token;

interface TokenServiceInterface
{
    /**
     * Add token to db
     *
     * @param $customerId
     * @param $paymentProductId
     * @param $token
     */
    public function add($customerId, $paymentProductId, $token);

    /**
     * Get token values from db
     *
     * @param int $customerId
     * @param int|null $paymentProductId
     * @return array
     */
    public function find($customerId, $paymentProductId = null);

    /**
     * @param int $customerId
     * @param string[] $tokens
     * @return void
     */
    public function deleteAll($customerId, $tokens = []);
}
