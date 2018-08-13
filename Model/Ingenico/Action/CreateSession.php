<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\CreateSession\SessionRequestFactory;
use Netresearch\Epayments\Model\Ingenico\Token\TokenServiceInterface;

/**
 * @link https://epayments-api.developer-ingenico.com/s2sapi/v1/en_US/php/sessions/create.html
 */
class CreateSession implements ActionInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var SessionRequestFactory
     */
    private $requestFactory;

    /**
     * @var TokenServiceInterface
     */
    private $tokenService;

    /**
     * CreateSession constructor.
     * @param ClientInterface $ingenicoClient
     * @param SessionRequestFactory $requestFactory
     * @param TokenServiceInterface $tokenService
     */
    public function __construct(
        ClientInterface $ingenicoClient,
        SessionRequestFactory $requestFactory,
        TokenServiceInterface $tokenService
    ) {
        $this->client = $ingenicoClient;
        $this->requestFactory = $requestFactory;
        $this->tokenService = $tokenService;
    }

    /**
     * Create a new session for the Client SDK.
     *
     * @param int|null $customerId
     * @return \Ingenico\Connect\Sdk\Domain\Sessions\SessionResponse
     */
    public function create($customerId = null)
    {
        if ($customerId === null) {
            $tokens = [];
        } else {
            $tokens = $this->tokenService->find($customerId);
        }
        $request = $this->requestFactory->create($tokens);

        $sessionResponse = $this->client->ingenicoCreateSession($request);

        $this->tokenService->deleteAll(
            $customerId,
            $sessionResponse->invalidTokens
        );

        return $sessionResponse;
    }
}
