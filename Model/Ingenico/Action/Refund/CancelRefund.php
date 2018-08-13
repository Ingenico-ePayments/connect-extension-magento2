<?php

namespace Netresearch\Epayments\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Sdk\Domain\Refund\RefundResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Action\AbstractAction;
use Netresearch\Epayments\Model\Ingenico\Action\ActionInterface;
use Netresearch\Epayments\Model\Ingenico\Action\RetrievePayment;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__cancel_post
 */
class CancelRefund extends AbstractAction implements ActionInterface
{
    /**
     * @var string[]
     */
    private $allowedStates = [
        StatusInterface::PENDING_APPROVAL,
        StatusInterface::REFUND_REQUESTED,
    ];

    /**
     * @var RetrievePayment
     */
    private $retrievePayment;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * CancelRefund constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param RetrievePayment $retrievePayment
     * @param ResolverInterface $statusResolver
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        RetrievePayment $retrievePayment,
        ResolverInterface $statusResolver
    ) {
        $this->retrievePayment = $retrievePayment;
        $this->statusResolver = $statusResolver;

        parent::__construct($statusResponseManager, $ingenicoClient, $transactionManager, $config);
    }

    /**
     * Cancel the creditmemo at the Ingenico API
     * and within Magento itself.
     *
     * @param Creditmemo $creditmemo
     * @throws LocalizedException
     */
    public function process(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $refundId = $creditmemo->getTransactionId();

        $payment = $order->getPayment();

        $refundResponse = $this->statusResponseManager->get($payment, $refundId);

        $isAllowedStatus = in_array(
            $refundResponse->status,
            $this->allowedStates
        );
        if (!$isAllowedStatus) {
            throw new LocalizedException(__("Cannot cancel refund with status $refundResponse->status"));
        }

        // Cancel refund via Ingenico API
        $this->ingenicoClient->ingenicoRefundCancel(
            $refundResponse->id,
            $order->getStoreId()
        );

        // Retrieve current status from api and update because
        // cancelRefund only returns a HTTP status code
        $this->retrievePayment->process($order);
        /** @var RefundResponse $response */
        $response = $this->statusResponseManager->get($payment, $refundId);

        if ($response->status !== StatusInterface::CANCELLED) {
            throw new LocalizedException(__('Cancelation was unsucessful'));
        }

        $this->postProcess($payment, $response);
    }
}
