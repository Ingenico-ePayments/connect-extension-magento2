<?php

namespace Ingenico\Connect\Model\Ingenico\Token;

class TokenService implements TokenServiceInterface
{
    /** @var TokenFactory */
    private $tokenFactory;

    /** @var \Ingenico\Connect\Model\ResourceModel\Token */
    private $tokenResource;

    /**
     * @param TokenFactory $tokenFactory
     * @param \Ingenico\Connect\Model\ResourceModel\Token $tokenResource
     */
    public function __construct(
        TokenFactory $tokenFactory,
        \Ingenico\Connect\Model\ResourceModel\Token $tokenResource
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->tokenResource = $tokenResource;
    }

    /**
     * {@inheritdoc}
     */
    public function add($customerId, $paymentProductId, $token)
    {
        /** @var \Ingenico\Connect\Model\Ingenico\Token\Token[] $tokenModel */
        $tokenValues = $this->find($customerId, $paymentProductId);
        if (!in_array($token, $tokenValues)) {
            /** @var \Ingenico\Connect\Model\Ingenico\Token\Token $tokenModel */
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
        $tokenModel = $this->tokenFactory->create();
        $tokenCollection = $tokenModel->getCollection();

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
            $tokenModel = $this->tokenFactory->create();
            /** @var \Ingenico\Connect\Model\ResourceModel\Token\Collection $tokenCollection */
            $tokenCollection = $tokenModel->getCollection();
            $tokenCollection->setCustomerId($customerId)
                            ->addFieldToFilter(
                                'token',
                                ['in', $tokens]
                            );
            /** @var Token $token */
            foreach ($tokenCollection->getItems() as $token) {
                $token->isDeleted(true);
            }
            $tokenCollection->save();
        }
    }
}
