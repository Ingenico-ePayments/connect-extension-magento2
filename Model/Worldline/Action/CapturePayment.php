<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\Domain\Payment\ApprovePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderApprovePayment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderReferencesApprovePayment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Worldline\Connect\Helper\Data;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\MerchantReference;

class CapturePayment extends AbstractAction implements ActionInterface
{
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        private readonly ApprovePaymentRequest $approvePaymentRequest,
        private readonly OrderApprovePayment $orderApprovePayment,
        private readonly OrderReferencesApprovePayment $orderReferencesApprovePayment,
        private readonly MerchantReference $merchantReference
    ) {
        parent::__construct(
            $statusResponseManager,
            $worldlineClient,
            $transactionManager,
            $config
        );
    }

    public function process(OrderPayment $payment, mixed $amount): Payment
    {
        $order = $payment->getOrder();
        $this->orderReferencesApprovePayment->merchantReference = $this->merchantReference
            ->generateMerchantReferenceForOrder($order);

        $this->orderApprovePayment->references = $this->orderReferencesApprovePayment;
        $this->approvePaymentRequest->order = $this->orderApprovePayment;
        $this->approvePaymentRequest->amount = Data::formatWorldlineAmount($amount);

        $response = $this->worldlineClient->worldlinePaymentApprove(
            $payment->getLastTransId(),
            $this->approvePaymentRequest,
            $payment->getOrder()->getStoreId()
        );

        $this->postProcess($payment, $response->payment);

        return $response->payment;
    }
}
