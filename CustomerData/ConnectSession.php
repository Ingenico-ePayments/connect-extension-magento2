<?php

namespace Ingenico\Connect\CustomerData;

use Exception;
use Ingenico\Connect\Api\SessionManagerInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ConnectSession
 * @package Ingenico\Connect\CustomerData
 */
class ConnectSession implements SectionSourceInterface
{
    /** @var Session */
    private $checkoutSession;
    
    /** @var SessionManagerInterface */
    private $sessionManager;
    
    /**
     * @param Session $checkoutSession
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(Session $checkoutSession, SessionManagerInterface $sessionManager)
    {
        $this->checkoutSession = $checkoutSession;
        $this->sessionManager = $sessionManager;
    }
    
    /**
     * Get Session data for customer
     *
     * @return string[]
     */
    public function getSectionData()
    {
        try {
            $customerId = $this->checkoutSession->getQuote()->getCustomerId();
            if ($customerId === null) {
                return [
                    'data' => $this->sessionManager->createAnonymousSession()
                ];
            }
            
            return [
                'data' => $this->sessionManager->createCustomerSession($customerId)
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
