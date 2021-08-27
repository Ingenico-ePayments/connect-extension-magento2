<?php

namespace Ingenico\Connect\Plugin\Magento\Sales\Model\Order;

use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Model\Transaction\TransactionManager;
use Magento\Sales\Model\Order\Payment\Transaction;

class Creditmemo
{
    /**
     * @var TransactionManager
     */
    private $transactionManager;

    public function __construct(TransactionManager $transactionManager)
    {
        $this->transactionManager = $transactionManager;
    }

    public function afterCanCancel(\Magento\Sales\Model\Order\Creditmemo $subject, $response)
    {
        if ($subject->getOrder()->getPayment()->getMethod() !== ConfigProvider::CODE) {
            return $response;
        }

        if ($transaction = $this->transactionManager->retrieveTransaction((string) $subject->getTransactionId())) {
            $rawInfo = $transaction->getAdditionalInformation(Transaction::RAW_DETAILS);
            if (is_array($rawInfo) && array_key_exists('status', $rawInfo)) {
                return $rawInfo['status'] === StatusInterface::PENDING_APPROVAL ? $response : false;
            }
        }

        return $response;
    }
}
