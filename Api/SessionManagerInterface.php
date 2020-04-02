<?php

namespace Ingenico\Connect\Api;

use Ingenico\Connect\Api\Data\SessionInterface;

interface SessionManagerInterface
{
    /**
     * @api
     *
     * @param int $customerId
     * @return \Ingenico\Connect\Api\Data\SessionInterface
     */
    public function createCustomerSession($customerId): SessionInterface;
    
    /**
     * @api
     *
     * @return \Ingenico\Connect\Api\Data\SessionInterface
     */
    public function createAnonymousSession(): SessionInterface;
}
