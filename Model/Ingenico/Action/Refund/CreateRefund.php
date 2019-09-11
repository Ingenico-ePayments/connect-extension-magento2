<?php

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Helper\Data as DataHelper;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Action\AbstractAction;
use Ingenico\Connect\Model\Ingenico\Action\ActionInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\CallContextBuilder;
use Ingenico\Connect\Model\Ingenico\MerchantReference;
use Ingenico\Connect\Model\Ingenico\RefundRequestBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Refund\RefundHandlerInterface;
use Ingenico\Connect\Model\Ingenico\Status\ResolverInterface;
use Ingenico\Connect\Model\StatusResponseManager;
use Ingenico\Connect\Model\Transaction\TransactionManager;

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
     * @var MerchantReference
     */
    private $merchantReference;

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
     * @param MerchantReference $merchantReference
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $ePaymentsConfig,
        DateTime $dateTime,
        ResolverInterface $statusResolver,
        RefundRequestBuilder $refundRequestBuilder,
        CallContextBuilder $callContextBuilder,
        MerchantReference $merchantReference
    ) {
        $this->dateTime = $dateTime;
        $this->statusResolver = $statusResolver;
        $this->refundRequestbuilder = $refundRequestBuilder;
        $this->callContextBuilder = $callContextBuilder;
        $this->merchantReference = $merchantReference;

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
        $this->refundRequestbuilder->setMerchantReference($this->merchantReference->generateMerchantReference($order));

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

        $payment->setPreparedMessage(
            sprintf(
                'Successfully processed notification about status %s with statusCode %s.',
                $response->status,
                $response->statusOutput->statusCode
            )
        );

        $this->postProcess($payment, $response);
    }
}
