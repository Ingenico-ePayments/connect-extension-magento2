<?php

namespace Ingenico\Connect\Model\Ingenico\Session;

use Ingenico\Connect\Api\Data\SessionInterface;
use Ingenico\Connect\Api\Data\SessionInterfaceFactory;
use Ingenico\Connect\Sdk\Domain\Sessions\SessionResponse;

class SessionBuilder
{
    /** @var SessionFactory */
    protected $sessionFactory;
    
    public function __construct(SessionInterfaceFactory $sessionFactory)
    {
        $this->sessionFactory = $sessionFactory;
    }
    
    public function build(SessionResponse $sessionResponse): SessionInterface
    {
        $session = $this->sessionFactory->create();
        $session->fromJson($sessionResponse->toJson());
        
        return $session;
    }
}
