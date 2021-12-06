<?php

namespace Ingenico\Connect\Model\Ingenico\Token;

interface TokenServiceInterface
{
    /**
     * Get token values from db
     *
     * @param int $customerId
     * @return array
     */
    public function find($customerId);

    /**
     * @param int $customerId
     * @param string[] $tokens
     * @return void
     */
    public function deleteAll($customerId, $tokens = []);
}
