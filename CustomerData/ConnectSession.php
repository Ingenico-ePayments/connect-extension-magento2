<?php

declare(strict_types=1);

namespace Worldline\Connect\CustomerData;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Worldline\Connect\Api\SessionManagerInterface;

class ConnectSession implements SectionSourceInterface
{
    public function __construct(
        private readonly Session $checkoutSession,
        private readonly SessionManagerInterface $sessionManager
    ) {
    }

    /**
     * @return array<string>
     */
    public function getSectionData(): array
    {
        try {
            $customerId = $this->checkoutSession->getQuote()->getCustomerId();
            return [
                'data' => $customerId !== null ?
                    $this->sessionManager->createCustomerSession($customerId) :
                    $this->sessionManager->createAnonymousSession(),
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
