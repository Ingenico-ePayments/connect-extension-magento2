<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\Action\Refund;

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\CallContextBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Refund\RefundRequestBuilder;
use Worldline\Connect\Model\Worldline\Status\Refund\ResolverInterface;

/**
 * Class CreateRefund
 *
 * @package Worldline\Connect\Model\Worldline\Action\Refund
 */
class CreateRefund extends AbstractRefundAction
{
    /**
     * @var RefundRequestBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $refundRequestBuilder;

    /**
     * @var CallContextBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $callContextBuilder;

    /**
     * @var ClientInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $worldlineClient;

    /**
     * @var ResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $statusResolver;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        RefundRequestBuilder $refundRequestBuilder,
        CallContextBuilder $callContextBuilder,
        ClientInterface $worldlineClient,
        ResolverInterface $statusResolver
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->refundRequestBuilder = $refundRequestBuilder;
        $this->callContextBuilder = $callContextBuilder;
        $this->worldlineClient = $worldlineClient;
        $this->statusResolver = $statusResolver;
    }

    protected function performRefundAction(OrderInterface $order, CreditmemoInterface $creditMemo)
    {
        $payment = $order->getPayment();
        $amount = $creditMemo->getGrandTotal();

        $worldlinePaymentId = $payment->getLastTransId();

        $worldlinePayment = $this->worldlineClient->worldlinePayment($worldlinePaymentId, $creditMemo->getStoreId());

        if ($worldlinePayment->statusOutput->isRefundable) {
            $response = $this->worldlineClient->worldlineRefund(
                $worldlinePaymentId,
                $this->refundRequestBuilder->build($order, (float) $amount),
                null,
                $creditMemo->getStoreId()
            );
            // Call status resolver:
            $this->statusResolver->resolve(
                $creditMemo,
                $response
            );
        } elseif ($worldlinePayment->statusOutput->isCancellable) {
            // phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
            $response = $this->worldlineClient->worldlinePaymentCancel($worldlinePaymentId, $creditMemo->getStoreId());
        }

        $this->persist($order, $creditMemo);
    }
}
