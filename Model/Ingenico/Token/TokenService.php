<?php

namespace Ingenico\Connect\Model\Ingenico\Token;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentTokenRepository;

use function in_array;

class TokenService implements TokenServiceInterface
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;

    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepository $paymentTokenRepository
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function find($customerId)
    {
        $tokens = [];

        /** @var PaymentTokenInterface[] $paymentTokens */
        $paymentTokens = $this->paymentTokenManagement->getVisibleAvailableTokens($customerId);
        foreach ($paymentTokens as $paymentToken) {
            if ($paymentToken->getIsActive() && $paymentToken->getIsVisible()) {
                $tokens[] = $paymentToken->getGatewayToken();
            }
        }

        return array_unique($tokens);
    }

    /**
     * @param int $customerId
     * @param array $tokens
     * @throws \Exception
     */
    public function deleteAll($customerId, $tokens = [])
    {
        if ($customerId && !empty($tokens)) {
            /** @var PaymentTokenInterface[] $paymentTokens */
            $paymentTokens = $this->paymentTokenManagement->getVisibleAvailableTokens($customerId);
            foreach ($paymentTokens as $paymentToken) {
                if (in_array($paymentToken->getGatewayToken(), $tokens)) {
                    $this->paymentTokenRepository->delete($paymentToken);
                }
            }
        }
    }
}
