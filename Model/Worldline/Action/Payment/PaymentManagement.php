<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action\Payment;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Worldline\Connect\Api\OrderPaymentManagementInterface;
use Worldline\Connect\Api\PaymentManagementInterface;
use Worldline\Connect\Model\Transaction\TransactionManagerInterface;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;

class PaymentManagement implements PaymentManagementInterface
{
    /**
     * @var OrderPaymentManagementInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderPaymentManagement;

    /**
     * @var ClientInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $worldlineClient;

    /**
     * @var TransactionManagerInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $transactionManager;

    /**
     * @var OrderRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    public function __construct(
        OrderPaymentManagementInterface $orderPaymentManagement,
        ClientInterface $worldlineClient,
        TransactionManagerInterface $transactionManager,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderPaymentManagement = $orderPaymentManagement;
        $this->transactionManager = $transactionManager;
        $this->worldlineClient = $worldlineClient;
        $this->orderRepository = $orderRepository;
    }

    public function cancelApproval(InvoiceInterface $invoice): void
    {
        $order = $this->orderRepository->get($invoice->getOrderId());
        $currentStatus = $this->orderPaymentManagement->getWorldlinePaymentStatus($order->getPayment());

        if ($currentStatus === StatusInterface::CAPTURE_REQUESTED) {
            $transactionId = $invoice->getTransactionId();
            $this->worldlineClient->worldlineCancelApproval($transactionId);

            $transaction = $this->transactionManager->retrieveTransaction($transactionId);
            if ($transaction) {
                $transaction->setIsClosed(1);
                $this->transactionManager->updateTransaction($transaction);
            }
        }
    }
}
