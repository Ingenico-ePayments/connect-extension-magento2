<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Session;

use Ingenico\Connect\Api\Data\SessionInterface;
use Ingenico\Connect\Api\SessionManagerInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\CreateSession\RequestBuilder;
use Ingenico\Connect\Model\Ingenico\Token\TokenServiceInterface;

class SessionManager implements SessionManagerInterface
{
    /** @var ClientInterface */
    protected $ingenicoClient;
    
    /** @var RequestBuilder */
    protected $createSessionRequestBuilder;
    
    /** @var TokenServiceInterface */
    protected $tokenService;
    
    /** @var SessionBuilder */
    protected $sessionBuilder;
    
    public function __construct(
        ClientInterface $ingenicoClient,
        RequestBuilder $createSessionRequestBuilder,
        TokenServiceInterface $tokenService,
        SessionBuilder $sessionBuilder
    ) {
        $this->ingenicoClient = $ingenicoClient;
        $this->createSessionRequestBuilder = $createSessionRequestBuilder;
        $this->tokenService = $tokenService;
        $this->sessionBuilder = $sessionBuilder;
    }
    
    /**
     * @param int $customerId
     * @return SessionInterface
     */
    public function createCustomerSession($customerId): SessionInterface
    {
        return $this->createSession($customerId);
    }
    
    /**
     * @return SessionInterface
     */
    public function createAnonymousSession(): SessionInterface
    {
        return $this->createSession();
    }
    
    /**
     * @param int|null $customerId
     * @return SessionInterface
     */
    protected function createSession($customerId = null): SessionInterface
    {
        $tokens = $customerId === null ? [] : $this->tokenService->find($customerId);
        $createSessionRequest = $this->createSessionRequestBuilder->build($tokens);
        $createSessionResponse = $this->ingenicoClient->ingenicoCreateSession($createSessionRequest);
        
        if ($customerId !== null) {
            $this->tokenService->deleteAll($customerId, $createSessionResponse->invalidTokens);
        }
        
        return $this->sessionBuilder->build($createSessionResponse);
    }
}
