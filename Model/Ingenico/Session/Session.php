<?php

namespace Ingenico\Connect\Model\Ingenico\Session;

use Ingenico\Connect\Api\Data\SessionInterface;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionResponse;

class Session extends SessionResponse implements SessionInterface
{
    
    /**
     * @return string|null
     * @api
     */
    public function getAssetUrl()
    {
        return $this->assetUrl;
    }
    
    /**
     * @return string|null
     * @api
     */
    public function getClientApiUrl()
    {
        return $this->clientApiUrl;
    }
    
    /**
     * @return string|null
     * @api
     */
    public function getClientSessionId()
    {
        return $this->clientSessionId;
    }
    
    /**
     * @return string|null
     * @api
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }
    
    /**
     * @return string[]
     * @api
     */
    public function getInvalidTokens()
    {
        return $this->invalidTokens;
    }
    
    /**
     * @return string|null
     * @api
     */
    public function getRegion()
    {
        return $this->region;
    }
}
