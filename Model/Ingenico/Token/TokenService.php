<?php

namespace Ingenico\Connect\Model\Ingenico\Token;

use Ingenico\Connect\Model\Ingenico\Token\TokenFactory;
use Ingenico\Connect\Model\ResourceModel\Token as TokenResource;
use Ingenico\Connect\Model\ResourceModel\Token\CollectionFactory as TokenCollectionFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

class TokenService implements TokenServiceInterface
{
    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    /**
     * @var TokenResource
     */
    private $tokenResource;

    /**
     * @var TokenCollectionFactory
     */
    private $tokenCollectionFactory;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * TokenService constructor.
     *
     * @param TokenFactory $tokenFactory
     * @param TokenCollectionFactory $tokenCollectionFactory
     * @param TokenResource $tokenResource
     */
    public function __construct(
        TokenFactory $tokenFactory,
        TokenCollectionFactory $tokenCollectionFactory,
        TokenResource $tokenResource,
        PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->tokenResource = $tokenResource;
        $this->tokenCollectionFactory = $tokenCollectionFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * {@inheritdoc}
     */
    public function add($customerId, $paymentProductId, $token)
    {
        $tokenValues = $this->find($customerId, $paymentProductId);

        if (!in_array($token, $tokenValues)) {
            $tokenModel = $this->tokenFactory->create();
            $tokenModel->setCustomerId($customerId);
            $tokenModel->setPaymentProductId($paymentProductId);
            $tokenModel->setToken($token);
            $this->tokenResource->save($tokenModel);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find($customerId, $paymentProductId = null)
    {
        $tokenCollection = $this->tokenCollectionFactory->create();
        $tokenCollection->setCustomerId($customerId);
        if ($paymentProductId) {
            $tokenCollection->setPaymentProductId($paymentProductId);
        }

        $tokenValues = $tokenCollection->getColumnValues('token');

        $tokens = array_values(array_filter($tokenValues));

        /** @var PaymentTokenInterface[] $paymentTokens */
        $paymentTokens = $this->paymentTokenManagement->getListByCustomerId($customerId);
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
            $tokenCollection = $this->tokenCollectionFactory->create();
            $tokenCollection
                ->setCustomerId($customerId)
                ->addFieldToFilter('token', ['in', $tokens]);
            /** @var Token $token */
            foreach ($tokenCollection->getItems() as $token) {
                $token->isDeleted(true);
            }

            $tokenCollection->save();
        }
    }
}
