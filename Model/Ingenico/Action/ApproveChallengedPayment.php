<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\ConfigProvider;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\GlobalCollect\Status\OrderStatusHelper;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__processchallenged_post
 */
class ApproveChallengedPayment extends AbstractAction implements ActionInterface
{
    /**
     * @var OrderStatusHelper
     */
    private $gcOrderStatusHelper;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * ApproveChallengedPayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param OrderStatusHelper $orderStatusHelper
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        OrderStatusHelper $orderStatusHelper,
        ResolverInterface $statusResolver
    ) {
        $this->gcOrderStatusHelper = $orderStatusHelper;
        $this->statusResolver = $statusResolver;

        parent::__construct($statusResponseManager, $ingenicoClient, $transactionManager, $config);
    }

    /**
     * Accept payment
     *
     * @param Order $order
     * @throws LocalizedException
     */
    public function process(Order $order)
    {
        if (!$this->isIngenicoFraudOrder($order)) {
            throw new LocalizedException(
                __('This order was not placed via Ingenico ePayments or was not detected as fraud')
            );
        }

        /** @var Order\Payment $payment */
        $payment = $order->getPayment();
        $paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        $response = $this->ingenicoClient->ingenicoPaymentAccept($paymentId, $order->getStoreId());
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
     * Check if order was marked as fraud by Ingenico
     *
     * @param Order $order
     * @return bool
     */
    private function isIngenicoFraudOrder(Order $order)
    {
        return $this->isIngenicoOrder($order) && $order->getStatus() === Order::STATUS_FRAUD;
    }

    /**
     * Check if is ingenico order
     *
     * @param Order $order
     * @return bool
     */
    private function isIngenicoOrder(Order $order)
    {
        /** @var MethodInterface $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();

        return $paymentMethod->getCode() == ConfigProvider::CODE;
    }
}
