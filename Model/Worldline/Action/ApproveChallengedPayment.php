<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Status\OrderStatusHelper;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__processchallenged_post
 */
class ApproveChallengedPayment extends AbstractAction implements ActionInterface
{
    /**
     * @var OrderStatusHelper
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $gcOrderStatusHelper;

    /**
     * @var ResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusResolver;

    /**
     * ApproveChallengedPayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $worldlineClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param OrderStatusHelper $orderStatusHelper
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        OrderStatusHelper $orderStatusHelper,
        ResolverInterface $statusResolver
    ) {
        $this->gcOrderStatusHelper = $orderStatusHelper;
        $this->statusResolver = $statusResolver;

        parent::__construct($statusResponseManager, $worldlineClient, $transactionManager, $config);
    }

    /**
     * Accept payment
     *
     * @param Order $order
     * @throws LocalizedException
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function process(Order $order)
    {
        if (!$this->isWorldlineFraudOrder($order)) {
            throw new LocalizedException(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __('This order was not detected as fraud')
            );
        }

        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        $paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        if ($paymentId === null) {
            throw new LocalizedException(
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                __('This order was not placed via Worldline ePayments')
            );
        }

        $response = $this->worldlineClient->worldlinePaymentAccept($paymentId, $order->getStoreId());
        $this->statusResolver->resolve($order, $response);
        $payment->setIsTransactionClosed(false);

        if ($this->gcOrderStatusHelper->shouldOrderSkipPaymentReview($response)) {
            $payment->setIsTransactionApproved(true);
            $payment->update(false);
            /** @var Order\Invoice $invoice */
            foreach ($order->getInvoiceCollection() as $invoice) {
                if ($invoice->getTransactionId() === $response->id) {
                    $invoice->setState(Order\Invoice::STATE_OPEN);
                    $order->addRelatedObject($invoice);
                }
            }
        }

        $this->postProcess($payment, $response);
    }

    /**
     * Check if order was marked as fraud by Worldline
     *
     * @param Order $order
     * @return bool
     */
    private function isWorldlineFraudOrder(Order $order)
    {
        return $order->getStatus() === Order::STATUS_FRAUD;
    }
}
