<?php

namespace Ingenico\Connect\Model\Ingenico\CreateSession;

use Ingenico\Connect\Sdk\Domain\Sessions\SessionRequest;
use Magento\Framework\ObjectManagerInterface;

class SessionRequestFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $tokens
     * @return SessionRequest
     */
    public function create(array $tokens = [])
    {
        /** @var SessionRequest $request */
        $request = $this->objectManager->create(SessionRequest::class);
        if (!empty($tokens)) {
            $request->tokens = $tokens;
        }

        return $request;
    }
}
