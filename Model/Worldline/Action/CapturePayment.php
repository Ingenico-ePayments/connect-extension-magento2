<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

use Ingenico\Connect\Sdk\Domain\Payment\CapturePaymentRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Helper\Data as DataHelper;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\StatusInterface;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__approve_post
 */
class CapturePayment extends AbstractAction implements ActionInterface
{
    /**
     * @var CapturePaymentRequest
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $capturePaymentRequest;

    /**
     * CapturePayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $worldlineClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param CapturePaymentRequest $capturePaymentRequest
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        CapturePaymentRequest $capturePaymentRequest
    ) {
        $this->capturePaymentRequest = $capturePaymentRequest;

        parent::__construct(
            $statusResponseManager,
            $worldlineClient,
            $transactionManager,
            $config
        );
    }

    /**
     * Capture payment with Worldline
     *
     * @param Order $order
     * @param $amount
     * @throws LocalizedException
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
    public function process(Order $order, $amount)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();

        $worldlinePaymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        $authResponseObject = $this->statusResponseManager->get($payment, $worldlinePaymentId);

        $worldlinePaymentId = $authResponseObject->id;

        $response = $this->capturePayment($worldlinePaymentId, $payment, $amount);

        if ($response->status === StatusInterface::CAPTURE_REQUESTED) {
            $payment->setIsTransactionPending(true); // set order status to 'Payment Review'
        }

        $payment->setPreparedMessage(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            sprintf(
                'Successfully processed notification about status %s with statusCode %s.',
                $response->status,
                $response->statusOutput->statusCode
            )
        );

        $this->postProcess($payment, $response);
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * Capture payments via Ogone api.
     * With no further settings the request will always capture
     * the full amount
     *
     * @param string $worldlinePaymentId
     * @param Payment $payment
     * @param int $amount
     * @param bool $isFinal
     * @return \Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    private function capturePayment(
        $worldlinePaymentId,
        Payment $payment,
        $amount,
        $isFinal = false
    ) {
        $request = $this->capturePaymentRequest;
        $request->amount = DataHelper::formatWorldlineAmount($amount);
        $request->isFinal = $isFinal;

        $response = $this->worldlineClient->worldlinePaymentCapture(
            $worldlinePaymentId,
            $request,
            $payment->getOrder()->getStoreId()
        );

        return $response;
    }
}
