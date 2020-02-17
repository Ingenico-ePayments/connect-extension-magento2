<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\HostedCheckout;

use Ingenico\Connect\Model\Ingenico\Token\TokenServiceInterface;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\GetHostedCheckoutResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

/**
 * Token Management for Hosted Checkout
 *
 * @package Ingenico\Connect\Model\Ingenico\Action\HostedCheckout
 */
class TokenManagement
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MetaDataManagement
     */
    private $metaDataManagement;

    /**
     * @var TokenServiceInterface
     */
    private $tokenService;

    public function __construct(
        LoggerInterface $logger,
        MetaDataManagement $metaDataManagement,
        TokenServiceInterface $tokenService
    ) {
        $this->logger = $logger;
        $this->metaDataManagement = $metaDataManagement;
        $this->tokenService = $tokenService;
    }

    public function processTokens(OrderInterface $order, GetHostedCheckoutResponse $statusResponse)
    {
        $tokens = $statusResponse->createdPaymentOutput->tokens;

        if ($tokens) {
            $customerId = $order->getCustomerId();

            if (!$customerId) {
                $this->logger->info('Received token for guest customer');
                return;
            }

            try {
                $paymentProductId = $this->metaDataManagement->getPaymentProductId($order, $statusResponse);
            } catch (LocalizedException $exception) {
                $this->logger->error($exception->getMessage());
                return;
            }

            foreach (array_filter(explode(',', $tokens)) as $token) {
                $this->tokenService->add($customerId, $paymentProductId, $token);
            }
        }
    }
}
