<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\CreateSession;

use Ingenico\Connect\Sdk\Domain\Sessions\SessionRequest;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionRequestFactory;

class RequestBuilder
{
    /** @var SessionRequestFactory */
    protected $sessionRequestFactory;
    
    /**
     * @param SessionRequestFactory $sessionRequestFactory
     */
    public function __construct(SessionRequestFactory $sessionRequestFactory)
    {
        $this->sessionRequestFactory = $sessionRequestFactory;
    }
    
    /**
     * @param array $tokens
     * @return SessionRequest
     */
    public function build(array $tokens = [])
    {
        $sessionRequest = $this->sessionRequestFactory->create();
        if (count($tokens)) {
            $sessionRequest->tokens = $tokens;
        }
        
        return $sessionRequest;
    }
}
