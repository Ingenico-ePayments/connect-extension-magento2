<?php

namespace Netresearch\Epayments\Model\Ingenico\Action\Refund;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Helper\Data as DataHelper;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Action\AbstractAction;
use Netresearch\Epayments\Model\Ingenico\Action\ActionInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\CallContextBuilder;
use Netresearch\Epayments\Model\Ingenico\RefundRequestBuilder;
use Netresearch\Epayments\Model\Ingenico\Status\Refund\RefundHandlerInterface;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__refund_post
 */
class CreateRefund extends AbstractAction implements ActionInterface
{
    const EMAIL_MESSAGE_TYPE = 'html';

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var RefundRequestBuilder
     */
    private $refundRequestbuilder;

    /**
     * @var CallContextBuilder
     */
    private $callContextBuilder;

    /**
     * CreateRefund constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $ePaymentsConfig
     * @param DateTime $dateTime
     * @param ResolverInterface $statusResolver
     * @param RefundRequestBuilder $refundRequestBuilder
     * @param CallContextBuilder $callContextBuilder
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $ePaymentsConfig,
        DateTime $dateTime,
        ResolverInterface $statusResolver,
        RefundRequestBuilder $refundRequestBuilder,
        CallContextBuilder $callContextBuilder
    ) {
        $this->dateTime = $dateTime;
        $this->statusResolver = $statusResolver;
        $this->refundRequestbuilder = $refundRequestBuilder;
        $this->callContextBuilder = $callContextBuilder;

        parent::__construct($statusResponseManager, $ingenicoClient, $transactionManager, $ePaymentsConfig);
    }

    /**
     * Create refund
     *
     * @param Order $order
     * @param float $amount
     */
    public function process(Order $order, $amount)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $ingenicoPaymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        $billing = $order->getBillingAddress();
        if ($billing !== null) {
            $this->refundRequestbuilder->setCountryCode($billing->getCountryId());
        }
        $this->refundRequestbuilder->setAmount(DataHelper::formatIngenicoAmount($amount));
        $this->refundRequestbuilder->setCurrencyCode($order->getBaseCurrencyCode());
        $this->refundRequestbuilder->setCustomerEmail($order->getCustomerEmail() ?: '');
        $this->refundRequestbuilder->setCustomerLastname($order->getCustomerLastname() ?: '');
        $this->refundRequestbuilder->setEmailMessageType(self::EMAIL_MESSAGE_TYPE);
        $this->refundRequestbuilder->setMerchantReference($order->getIncrementId());
        $request = $this->refundRequestbuilder->create();

        $callContext = $this->callContextBuilder->create();

        $response = $this->ingenicoClient->ingenicoRefund(
            $ingenicoPaymentId,
            $request,
            $callContext,
            $order->getStoreId()
        );

        /** @var RefundHandlerInterface $handler */
        $handler = $this->statusResolver->getHandlerByType(ResolverInterface::TYPE_REFUND, $response->status);
        $handler->applyCreditmemo($payment->getCreditmemo());

        $payment->setLastTransId($response->id);

        $this->postProcess($payment, $response);
    }
}
