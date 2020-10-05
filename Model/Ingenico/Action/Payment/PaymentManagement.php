<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Payment;

use Ingenico\Connect\Api\OrderPaymentManagementInterface;
use Ingenico\Connect\Api\PaymentManagementInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Model\Transaction\TransactionManagerInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class PaymentManagement implements PaymentManagementInterface
{
    /**
     * @var OrderPaymentManagementInterface
     */
    private $orderPaymentManagement;

    /**
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var TransactionManagerInterface
     */
    private $transactionManager;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        OrderPaymentManagementInterface $orderPaymentManagement,
        ClientInterface $ingenicoClient,
        TransactionManagerInterface $transactionManager,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderPaymentManagement = $orderPaymentManagement;
        $this->transactionManager = $transactionManager;
        $this->ingenicoClient = $ingenicoClient;
        $this->orderRepository = $orderRepository;
    }

    public function cancelApproval(InvoiceInterface $invoice): void
    {
        $order = $this->orderRepository->get($invoice->getOrderId());
        $currentStatus = $this->orderPaymentManagement->getIngenicoPaymentStatus($order->getPayment());

        if ($currentStatus === StatusInterface::CAPTURE_REQUESTED) {
            $transactionId = $invoice->getTransactionId();
            $this->ingenicoClient->ingenicoCancelApproval($transactionId);

            $transaction = $this->transactionManager->retrieveTransaction($transactionId);
            if ($transaction) {
                $transaction->setIsClosed(1);
                $this->transactionManager->updateTransaction($transaction);
            }
        }
    }
}
