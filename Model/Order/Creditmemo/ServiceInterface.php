<?php

namespace Netresearch\Epayments\Model\Order\Creditmemo;

use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;

interface ServiceInterface
{
    /**
     * Retrieves the creditmemo that is currently being created or loads
     * the creditmemo via the transaction id from Ingenico.
     *
     * @param OrderPaymentInterface|Payment $payment
     * @param string $transactionId
     * @throws NotFoundException
     * @return CreditmemoInterface|Creditmemo
     */
    public function getCreditmemo(OrderPaymentInterface $payment, $transactionId = null);

    /**
     * Try to fetch Creditmemo by its transaction id
     *
     * @param $transactionId
     * @return CreditmemoInterface|Creditmemo
     * @throws NotFoundException
     */
    public function getCreditMemoByTxnId($transactionId);
}
