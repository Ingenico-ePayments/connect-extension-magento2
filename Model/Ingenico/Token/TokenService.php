<?php

namespace Ingenico\Connect\Model\Ingenico\Token;

use Ingenico\Connect\Model\ResourceModel\Token as TokenResource;
use Ingenico\Connect\Model\ResourceModel\Token\CollectionFactory as TokenCollectionFactory;

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
     * TokenService constructor.
     *
     * @param TokenFactory $tokenFactory
     * @param TokenCollectionFactory $tokenCollectionFactory
     * @param TokenResource $tokenResource
     */
    public function __construct(
        TokenFactory $tokenFactory,
        TokenCollectionFactory $tokenCollectionFactory,
        TokenResource $tokenResource
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->tokenResource = $tokenResource;
        $this->tokenCollectionFactory = $tokenCollectionFactory;
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

        return array_values(array_filter($tokenValues));
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
