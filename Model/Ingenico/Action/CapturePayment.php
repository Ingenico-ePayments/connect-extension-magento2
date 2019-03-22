<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Payment\CapturePaymentRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Helper\Data as DataHelper;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__approve_post
 */
class CapturePayment extends AbstractAction implements ActionInterface
{
    /**
     * @var CapturePaymentRequest
     */
    private $capturePaymentRequest;

    /**
     * CapturePayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param CapturePaymentRequest $capturePaymentRequest
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        CapturePaymentRequest $capturePaymentRequest
    ) {
        $this->capturePaymentRequest = $capturePaymentRequest;

        parent::__construct(
            $statusResponseManager,
            $ingenicoClient,
            $transactionManager,
            $config
        );
    }

    /**
     * Capture payment with Ingenico
     *
     * @param Order $order
     * @param $amount
     * @throws LocalizedException
     */
    public function process(Order $order, $amount)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();

        $ingenicoPaymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        $authResponseObject = $this->statusResponseManager->get($payment, $ingenicoPaymentId);

        $ingenicoPaymentId = $authResponseObject->id;

        $response = $this->capturePayment($ingenicoPaymentId, $payment, $amount);

        if ($response->status === StatusInterface::CAPTURE_REQUESTED) {
            $payment->setIsTransactionPending(true); // set order status to 'Payment Review'
        }

        $payment->setPreparedMessage(
            sprintf(
                'Successfully processed notification about status %s with statusCode %s.',
                $response->status,
                $response->statusOutput->statusCode
            )
        );

        $this->postProcess($payment, $response);
    }

    /**
     * Capture payments via Ogone api.
     * With no further settings the request will always capture
     * the full amount
     *
     * @param string $ingenicoPaymentId
     * @param Payment $payment
     * @param int $amount
     * @param bool $isFinal
     * @return \Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse
     */
    private function capturePayment(
        $ingenicoPaymentId,
        Payment $payment,
        $amount,
        $isFinal = false
    ) {
        $request = $this->capturePaymentRequest;
        $request->amount = DataHelper::formatIngenicoAmount($amount);
        $request->isFinal = $isFinal;

        $response = $this->ingenicoClient->ingenicoPaymentCapture(
            $ingenicoPaymentId,
            $request,
            $payment->getOrder()->getStoreId()
        );

        return $response;
    }
}
