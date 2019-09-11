<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\ResponseException;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\StatusInterface;

class UndoCapturePaymentRequest extends AbstractAction implements ActionInterface
{
    /**
     * @var string[]
     */
    private $allowedStates = [StatusInterface::CAPTURE_REQUESTED];

    /**
     * Undo capture payment
     *
     * @param Order $order
     * @throws ResponseException
     */
    public function process(Order $order)
    {
        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        $ingenicoPaymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        /** @var PaymentResponse $statusResponse */
        $statusResponse = $this->ingenicoClient->ingenicoPayment($ingenicoPaymentId);

        if (in_array($statusResponse->status, $this->allowedStates, true)) {
            $this->ingenicoClient->ingenicoCancelApproval($ingenicoPaymentId);

            /**
             * @var Order\Payment\Transaction $transaction
             */
            $transaction = $this->transactionManager->retrieveTransaction($ingenicoPaymentId);
            if ($transaction) {
                $transaction->setIsClosed(1);
                $this->transactionManager->updateTransaction($transaction);
            }
        }
    }
}
