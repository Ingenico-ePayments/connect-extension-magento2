<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Ingenico\Action\Refund;

use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\CallContextBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund\RefundRequestBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Refund\ResolverInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Ingenico\Connect\Model\Config;

/**
 * Class CreateRefund
 *
 * @package Ingenico\Connect\Model\Ingenico\Action\Refund
 */
class CreateRefund extends AbstractRefundAction
{
    /**
     * @var RefundRequestBuilder
     */
    private $refundRequestBuilder;

    /**
     * @var CallContextBuilder
     */
    private $callContextBuilder;

    /**
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        RefundRequestBuilder $refundRequestBuilder,
        CallContextBuilder $callContextBuilder,
        ClientInterface $ingenicoClient,
        ResolverInterface $statusResolver
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->refundRequestBuilder = $refundRequestBuilder;
        $this->callContextBuilder = $callContextBuilder;
        $this->ingenicoClient = $ingenicoClient;
        $this->statusResolver = $statusResolver;
    }

    protected function performRefundAction(OrderInterface $order, CreditmemoInterface $creditMemo)
    {
        $payment = $order->getPayment();
        $amount = $creditMemo->getBaseGrandTotal();

        $callContext = $this->callContextBuilder->create();
        $response = $this->ingenicoClient->ingenicoRefund(
            $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY),
            $this->refundRequestBuilder->build($order, (float) $amount),
            $callContext,
            $creditMemo->getStoreId()
        );

        // Call status resolver:
        $this->statusResolver->resolve(
            $creditMemo,
            $response
        );

        $this->persist($order, $creditMemo);
    }
}
