<?php

namespace Ingenico\Connect\Api\Data;

interface SessionInterface
{
    /**
     * @api
     * @return string|null
     */
    public function getAssetUrl();
    
    /**
     * @api
     * @return string|null
     */
    public function getClientApiUrl();
    
    /**
     * @api
     * @return string|null
     */
    public function getClientSessionId();
    
    /**
     * @api
     * @return string|null
     */
    public function getCustomerId();
    
    /**
     * @api
     * @return string[]|null
     */
    public function getInvalidTokens();
    
    /**
     * @api
     * @return string|null
     */
    public function getRegion();
}
