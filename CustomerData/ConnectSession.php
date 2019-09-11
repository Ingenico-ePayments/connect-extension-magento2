<?php

namespace Ingenico\Connect\CustomerData;

use Magento\Checkout\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Ingenico\Connect\Model\Ingenico\Action\CreateSession;

/**
 * Class ConnectSession
 * @package Ingenico\Connect\CustomerData
 */
class ConnectSession implements SectionSourceInterface
{
    /** @var Session */
    private $checkoutSession;

    /** @var CreateSession */
    private $createSessionAction;

    /**
     * ConnectSession constructor.
     * @param Session $checkoutSession
     * @param CreateSession $createSessionAction
     */
    public function __construct(
        Session $checkoutSession,
        CreateSession $createSessionAction
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->createSessionAction = $createSessionAction;
    }

    /**
     * Get Session data for customer
     *
     * @return string[]
     */
    public function getSectionData()
    {
        $customerId = $this->checkoutSession->getQuote()->getCustomerId();
        try {
            $response = $this->createSessionAction->create($customerId);
            return ['data' => $response];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
